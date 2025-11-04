<?php

use PHPUnit\Framework\TestCase;
use App\Chat\{Conversation, Message};

/**
 * Test Conversation model behavior
 */
class ConversationTest extends TestCase {
    
    public function test_creates_conversation(): void {
        $conv = new Conversation('session-123');
        
        $this->assertEquals('session-123', $conv->sessionId);
        $this->assertEmpty($conv->messages);
    }
    
    public function test_throws_exception_for_empty_session_id(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Session ID is required');
        
        new Conversation('');
    }
    
    public function test_adds_messages(): void {
        $conv = new Conversation('session-123');
        
        $msg1 = new Message(1, 'user', 'Hello');
        $msg2 = new Message(2, 'assistant', 'Hi there');
        
        $conv->addMessage($msg1);
        $conv->addMessage($msg2);
        
        $this->assertCount(2, $conv->messages);
        $this->assertEquals('Hello', $conv->messages[0]->content);
    }
    
    public function test_gets_history_with_limit(): void {
        $conv = new Conversation('session-123');
        
        // Add 10 messages
        for ($i = 1; $i <= 10; $i++) {
            $conv->addMessage(new Message($i, 'user', "Message $i"));
        }
        
        $history = $conv->getHistory(5);
        
        $this->assertCount(5, $history);
        $this->assertEquals('Message 6', $history[0]->content);
        $this->assertEquals('Message 10', $history[4]->content);
    }
    
    public function test_gets_all_messages_when_limit_exceeds_count(): void {
        $conv = new Conversation('session-123');
        
        $conv->addMessage(new Message(1, 'user', 'Message 1'));
        $conv->addMessage(new Message(2, 'user', 'Message 2'));
        
        $history = $conv->getHistory(10);
        
        $this->assertCount(2, $history);
    }
    
    public function test_gets_message_count(): void {
        $conv = new Conversation('session-123');
        
        $this->assertEquals(0, $conv->getMessageCount());
        
        $conv->addMessage(new Message(1, 'user', 'Hello'));
        $this->assertEquals(1, $conv->getMessageCount());
        
        $conv->addMessage(new Message(2, 'assistant', 'Hi'));
        $this->assertEquals(2, $conv->getMessageCount());
    }
}



