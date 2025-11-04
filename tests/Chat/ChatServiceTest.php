<?php

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use App\Retrieval\{Document, InMemoryVectorRepository};
use App\Chat\{InMemoryChatRepository, ChatService};
use App\Llm\MockLlmClient;

/**
 * Integration test for ChatService RAG flow
 */
class ChatServiceTest extends TestCase {
    
    private ChatService $chatService;
    private InMemoryVectorRepository $vectorRepo;
    private InMemoryChatRepository $chatRepo;
    
    protected function setUp(): void {
        $this->vectorRepo = new InMemoryVectorRepository();
        $this->chatRepo = new InMemoryChatRepository();
        $llmClient = new MockLlmClient();
        $logger = new NullLogger(); // No-op logger for tests
        
        $this->chatService = new ChatService(
            $this->chatRepo,
            $this->vectorRepo,
            $llmClient,
            $logger
        );
    }
    
    public function test_chat_with_no_documents(): void {
        $chunks = [];
        foreach ($this->chatService->chat('session-1', 'Hello') as $chunk) {
            $chunks[] = $chunk;
        }
        
        $this->assertNotEmpty($chunks);
        $fullResponse = implode('', $chunks);
        $this->assertNotEmpty($fullResponse);
    }
    
    public function test_chat_retrieves_relevant_documents(): void {
        // Add documents
        $this->vectorRepo->add(new Document(
            null,
            'PHP Basics',
            'PHP is a server-side scripting language for web development'
        ));
        
        $this->vectorRepo->add(new Document(
            null,
            'Python Basics',
            'Python is a high-level programming language'
        ));
        
        // Chat about PHP
        $chunks = [];
        foreach ($this->chatService->chat('session-1', 'Tell me about PHP') as $chunk) {
            $chunks[] = $chunk;
        }
        
        $this->assertNotEmpty($chunks);
    }
    
    public function test_chat_saves_conversation_history(): void {
        $sessionId = 'session-123';
        
        // Send a message
        foreach ($this->chatService->chat($sessionId, 'Hello') as $chunk) {
            // Stream chunks
        }
        
        // Check conversation was saved
        $conversation = $this->chatRepo->getConversation($sessionId);
        $this->assertNotNull($conversation);
        $this->assertEquals(2, $conversation->getMessageCount()); // User + Assistant
    }
    
    public function test_chat_saves_sources_in_assistant_message(): void {
        // Add a document
        $this->vectorRepo->add(new Document(
            null,
            'Test Doc',
            'Test content for retrieval testing'
        ));
        
        $sessionId = 'session-123';
        
        // Chat
        foreach ($this->chatService->chat($sessionId, 'test retrieval') as $chunk) {
            // Stream
        }
        
        // Check assistant message has sources
        $messages = $this->chatRepo->getMessages($sessionId);
        $assistantMessage = $messages[1]; // Second message is assistant
        
        $this->assertEquals('assistant', $assistantMessage->role);
        $this->assertNotNull($assistantMessage->sources);
        $this->assertNotEmpty($assistantMessage->sources);
    }
    
    public function test_chat_uses_conversation_history(): void {
        $sessionId = 'session-123';
        
        // First message
        foreach ($this->chatService->chat($sessionId, 'My name is Alice') as $chunk) {
            // Stream
        }
        
        // Second message (should have history context)
        foreach ($this->chatService->chat($sessionId, 'What is my name?') as $chunk) {
            // Stream
        }
        
        // Check we have 4 messages (2 user, 2 assistant)
        $messages = $this->chatRepo->getMessages($sessionId);
        $this->assertCount(4, $messages);
    }
    
    public function test_multiple_sessions_are_independent(): void {
        // Session 1
        foreach ($this->chatService->chat('session-1', 'Hello') as $chunk) {}
        
        // Session 2
        foreach ($this->chatService->chat('session-2', 'Hi there') as $chunk) {}
        
        $session1Messages = $this->chatRepo->getMessages('session-1');
        $session2Messages = $this->chatRepo->getMessages('session-2');
        
        $this->assertCount(2, $session1Messages);
        $this->assertCount(2, $session2Messages);
        $this->assertEquals('Hello', $session1Messages[0]->content);
        $this->assertEquals('Hi there', $session2Messages[0]->content);
    }
}



