<?php

use PHPUnit\Framework\TestCase;
use App\Chat\{InMemoryChatRepository, Conversation, Message};

/**
 * Test ChatRepository operations
 */
class ChatRepositoryTest extends TestCase {
    
    private InMemoryChatRepository $repo;
    
    protected function setUp(): void {
        $this->repo = new InMemoryChatRepository();
    }
    
    public function test_returns_null_for_nonexistent_conversation(): void {
        $result = $this->repo->getConversation('nonexistent');
        
        $this->assertNull($result);
    }
    
    public function test_saves_and_retrieves_conversation(): void {
        $conv = new Conversation('session-123');
        
        $this->repo->saveConversation($conv);
        $retrieved = $this->repo->getConversation('session-123');
        
        $this->assertNotNull($retrieved);
        $this->assertEquals('session-123', $retrieved->sessionId);
    }
    
    public function test_adds_message_to_new_conversation(): void {
        $msg = new Message(null, 'user', 'Hello');
        
        $this->repo->addMessage('new-session', $msg);
        
        $conv = $this->repo->getConversation('new-session');
        $this->assertNotNull($conv);
        $this->assertCount(1, $conv->messages);
    }
    
    public function test_adds_message_to_existing_conversation(): void {
        $conv = new Conversation('session-123');
        $this->repo->saveConversation($conv);
        
        $msg1 = new Message(null, 'user', 'First message');
        $msg2 = new Message(null, 'assistant', 'Second message');
        
        $this->repo->addMessage('session-123', $msg1);
        $this->repo->addMessage('session-123', $msg2);
        
        $retrieved = $this->repo->getConversation('session-123');
        $this->assertCount(2, $retrieved->messages);
    }
    
    public function test_auto_assigns_message_ids(): void {
        $msg1 = new Message(null, 'user', 'Message 1');
        $msg2 = new Message(null, 'user', 'Message 2');
        
        $this->repo->addMessage('session-123', $msg1);
        $this->repo->addMessage('session-123', $msg2);
        
        $this->assertNotNull($msg1->id);
        $this->assertNotNull($msg2->id);
        $this->assertNotEquals($msg1->id, $msg2->id);
    }
    
    public function test_gets_messages_with_limit(): void {
        for ($i = 1; $i <= 10; $i++) {
            $msg = new Message(null, 'user', "Message $i");
            $this->repo->addMessage('session-123', $msg);
        }
        
        $messages = $this->repo->getMessages('session-123', 5);
        
        $this->assertCount(5, $messages);
        $this->assertEquals('Message 6', $messages[0]->content);
    }
    
    public function test_gets_empty_array_for_nonexistent_session(): void {
        $messages = $this->repo->getMessages('nonexistent', 10);
        
        $this->assertEmpty($messages);
    }
    
    public function test_deletes_conversation(): void {
        $conv = new Conversation('session-123');
        $this->repo->saveConversation($conv);
        
        $deleted = $this->repo->deleteConversation('session-123');
        
        $this->assertTrue($deleted);
        $this->assertNull($this->repo->getConversation('session-123'));
    }
    
    public function test_delete_returns_false_for_nonexistent(): void {
        $deleted = $this->repo->deleteConversation('nonexistent');
        
        $this->assertFalse($deleted);
    }
}



