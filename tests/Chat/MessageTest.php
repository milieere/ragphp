<?php

use PHPUnit\Framework\TestCase;
use App\Chat\Message;

/**
 * Test Message model validation
 */
class MessageTest extends TestCase {
    
    public function test_creates_user_message(): void {
        $msg = new Message(
            id: 1,
            role: 'user',
            content: 'Hello, world!'
        );
        
        $this->assertEquals('user', $msg->role);
        $this->assertEquals('Hello, world!', $msg->content);
        $this->assertNull($msg->sources);
    }
    
    public function test_creates_assistant_message_with_sources(): void {
        $sources = [['id' => 1, 'title' => 'Doc 1']];
        $msg = new Message(
            id: 1,
            role: 'assistant',
            content: 'Here is my response',
            sources: $sources
        );
        
        $this->assertEquals('assistant', $msg->role);
        $this->assertCount(1, $msg->sources);
    }
    
    public function test_throws_exception_for_invalid_role(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid role');
        
        new Message(
            id: 1,
            role: 'invalid',
            content: 'Content'
        );
    }
    
    public function test_throws_exception_for_empty_content(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Content is required');
        
        new Message(
            id: 1,
            role: 'user',
            content: ''
        );
    }
    
    public function test_throws_exception_for_content_too_long(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Content too long');
        
        $longContent = str_repeat('a', 10001);
        new Message(
            id: 1,
            role: 'user',
            content: $longContent
        );
    }
    
    public function test_auto_sets_timestamp(): void {
        $before = time();
        $msg = new Message(id: 1, role: 'user', content: 'Test');
        $after = time();
        
        $this->assertGreaterThanOrEqual($before, $msg->timestamp);
        $this->assertLessThanOrEqual($after, $msg->timestamp);
    }
}



