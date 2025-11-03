<?php

/**
 * RAG Chatbot - Example Usage Script
 * 
 * This script demonstrates how to use the RAG chatbot without the HTTP layer.
 * Useful for testing and understanding the core functionality.
 */

require_once 'vendor/autoload.php';
require_once 'src/Retrieval/Models.php';
require_once 'src/Retrieval/VectorRepositoryInterface.php';
require_once 'src/Retrieval/InMemoryVectorRepository.php';
require_once 'src/Chat/Models.php';
require_once 'src/Chat/ChatRepositoryInterface.php';
require_once 'src/Chat/InMemoryChatRepository.php';
require_once 'src/Chat/ChatService.php';
require_once 'src/Llm/LlmClientInterface.php';
require_once 'src/Llm/MockLlmClient.php';

use App\Retrieval\{Document, InMemoryVectorRepository};
use App\Chat\{InMemoryChatRepository, ChatService};
use App\Llm\MockLlmClient;

echo "=== RAG Chatbot Example ===\n\n";

// 1. Initialize components
echo "1. Initializing components...\n";
$vectorRepo = new InMemoryVectorRepository();
$chatRepo = new InMemoryChatRepository();
$llmClient = new MockLlmClient();
$chatService = new ChatService($chatRepo, $vectorRepo, $llmClient);
echo "✓ Components initialized\n\n";

// 2. Add documents to knowledge base
echo "2. Adding documents to knowledge base...\n";

$doc1 = new Document(
    id: null,
    title: "What is RAG?",
    content: "RAG (Retrieval-Augmented Generation) is a technique that enhances large language models by retrieving relevant documents from a knowledge base before generating responses. This grounds the model's outputs in factual information."
);
$vectorRepo->add($doc1);
echo "✓ Added: {$doc1->title}\n";

$doc2 = new Document(
    id: null,
    title: "Benefits of RAG",
    content: "RAG provides several benefits: improved accuracy, reduced hallucinations, up-to-date information without retraining, and the ability to cite sources. It's particularly useful for domain-specific applications."
);
$vectorRepo->add($doc2);
echo "✓ Added: {$doc2->title}\n";

$doc3 = new Document(
    id: null,
    title: "How RAG Works",
    content: "RAG works in three steps: 1) User asks a question, 2) Relevant documents are retrieved from the knowledge base, 3) The LLM generates a response using both the question and retrieved context."
);
$vectorRepo->add($doc3);
echo "✓ Added: {$doc3->title}\n\n";

// 3. List all documents
echo "3. Documents in knowledge base:\n";
$allDocs = $vectorRepo->getAll();
foreach ($allDocs as $doc) {
    echo "   - [{$doc->id}] {$doc->title}\n";
}
echo "\n";

// 4. Test search functionality
echo "4. Testing search functionality...\n";
$searchQuery = "how does RAG work";
echo "   Query: \"{$searchQuery}\"\n";
$results = $vectorRepo->search($searchQuery, 2);
echo "   Found {" . count($results) . "} results:\n";
foreach ($results as $result) {
    echo "   - {$result->document->title} (score: {$result->score})\n";
}
echo "\n";

// 5. Chat interaction
echo "5. Starting chat conversation...\n";
$sessionId = "demo-session-" . time();
echo "   Session ID: {$sessionId}\n\n";

// First message
echo "6. User: \"What is RAG and what are its benefits?\"\n";
echo "   Assistant: ";
foreach ($chatService->chat($sessionId, "What is RAG and what are its benefits?") as $chunk) {
    echo $chunk;
    flush();
}
echo "\n\n";

// Second message (with history)
echo "7. User: \"How does it work?\"\n";
echo "   Assistant: ";
foreach ($chatService->chat($sessionId, "How does it work?") as $chunk) {
    echo $chunk;
    flush();
}
echo "\n\n";

// 8. View conversation history
echo "8. Conversation history:\n";
$messages = $chatRepo->getMessages($sessionId);
echo "   Total messages: " . count($messages) . "\n";
foreach ($messages as $i => $msg) {
    $role = strtoupper($msg->role);
    $preview = substr($msg->content, 0, 60) . (strlen($msg->content) > 60 ? '...' : '');
    echo "   [{$i}] {$role}: {$preview}\n";
    if ($msg->sources) {
        echo "       Sources: " . count($msg->sources) . " documents\n";
    }
}
echo "\n";

// 9. Test with no relevant documents
echo "9. Testing with unrelated query...\n";
echo "   User: \"What's the weather like?\"\n";
echo "   Assistant: ";
foreach ($chatService->chat($sessionId, "What's the weather like?") as $chunk) {
    echo $chunk;
    flush();
}
echo "\n\n";

// 10. Clean up
echo "10. Cleaning up...\n";
$deleted = $chatRepo->deleteConversation($sessionId);
echo "✓ Conversation deleted: " . ($deleted ? 'yes' : 'no') . "\n\n";

echo "=== Example Complete ===\n";



