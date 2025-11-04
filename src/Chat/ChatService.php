<?php

namespace App\Chat;

use App\Retrieval\VectorRepositoryInterface;
use App\Llm\LlmClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Orchestrates the RAG (Retrieval-Augmented Generation) chat flow
 * 1. Retrieves relevant documents
 * 2. Builds augmented prompt
 * 3. Streams LLM response
 * 4. Saves conversation history
 */
class ChatService {
    public function __construct(
        private ChatRepositoryInterface $chatRepo,
        private VectorRepositoryInterface $vectorRepo,
        private LlmClientInterface $llm,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Process a chat message with RAG and stream the response
     * 
     * @param string $sessionId Unique conversation identifier
     * @param string $userMessage User's input message
     * @return \Generator Yields chunks of the assistant's response
     */
    public function chat(string $sessionId, string $userMessage): \Generator {
        $this->logger->debug('  📚 Step 1: Searching for relevant documents...');
        // Step 1: Search for relevant documents
        $searchResults = $this->vectorRepo->search($userMessage, 3);
        $this->logger->info('  🔍 Found documents', ['count' => count($searchResults), 'titles' => array_map(fn($r) => $r->document->title, $searchResults)]);
        
        $this->logger->debug('  💭 Step 2: Retrieving conversation history...');
        // Step 2: Get conversation history for context
        $history = $this->chatRepo->getMessages($sessionId, 10);
        $this->logger->info('  📜 Retrieved history', ['message_count' => count($history)]);
        
        $this->logger->debug('  🔨 Step 3: Building augmented prompt...');
        // Step 3: Build augmented prompt
        $prompt = $this->buildPrompt($userMessage, $searchResults, $history);
        $this->logger->debug('  ✓ Prompt built', ['length' => strlen($prompt)]);
        
        $this->logger->debug('  🤖 Step 4: Streaming LLM response...');
        // Step 4: Stream response from LLM
        $fullResponse = '';
        foreach ($this->llm->streamCompletion($prompt) as $chunk) {
            $fullResponse .= $chunk;
            yield $chunk;
        }
        $this->logger->info('  ✓ LLM response complete', ['response_length' => strlen($fullResponse)]);
        
        $this->logger->debug('  💾 Step 5: Saving conversation...');
        // Step 5: Save conversation after streaming completes
        $sources = array_map(fn($r) => [
            'id' => $r->document->id,
            'title' => $r->document->title,
            'score' => $r->score
        ], $searchResults);
        
        // Save user message
        $this->chatRepo->addMessage($sessionId, new Message(
            id: null,
            role: 'user',
            content: $userMessage
        ));
        
        // Save assistant response with sources
        $this->chatRepo->addMessage($sessionId, new Message(
            id: null,
            role: 'assistant',
            content: $fullResponse,
            sources: $sources
        ));
        
        $this->logger->info('  ✅ Conversation saved successfully');
    }
    
    /**
     * Build the augmented prompt with context and history
     */
    private function buildPrompt(string $userMessage, array $searchResults, array $history): string {
        // Build context from retrieved documents
        $context = '';
        if (!empty($searchResults)) {
            $context = "Here are some relevant documents from the knowledge base:\n\n";
            foreach ($searchResults as $result) {
                $context .= "### {$result->document->title}\n";
                $context .= $result->document->content . "\n\n";
            }
        }
        
        // Build conversation history
        $historyText = '';
        if (!empty($history)) {
            $historyText = "Previous conversation:\n";
            foreach (array_slice($history, -5) as $msg) {
                $role = ucfirst($msg->role);
                $historyText .= "{$role}: {$msg->content}\n";
            }
            $historyText .= "\n";
        }
        
        // Combine everything into the final prompt
        $prompt = "You are a helpful AI assistant. Use the provided documents to answer the user's question accurately.\n\n";
        $prompt .= $context;
        $prompt .= $historyText;
        $prompt .= "User: {$userMessage}\n\nAssistant: ";
        
        return $prompt;
    }
}
