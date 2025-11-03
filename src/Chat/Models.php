<?php

namespace App\Chat;

/**
 * Represents a single message in a conversation
 */
class Message {
    public function __construct(
        public ?int $id,
        public string $role,        // 'user' | 'assistant'
        public string $content,
        public ?array $sources = null,
        public int $timestamp = 0
    ) {
        if (!in_array($role, ['user', 'assistant', 'system'])) {
            throw new \InvalidArgumentException("Invalid role: {$role}. Must be 'user', 'assistant', or 'system'");
        }
        if (empty($content)) {
            throw new \InvalidArgumentException('Content is required');
        }
        if (strlen($content) > 10000) {
            throw new \InvalidArgumentException('Content too long (max 10000 characters)');
        }
        if ($this->timestamp === 0) {
            $this->timestamp = time();
        }
    }
}

/**
 * Represents a conversation with multiple messages
 */
class Conversation {
    /** @var Message[] */
    public array $messages = [];
    
    public function __construct(
        public string $sessionId,
        public int $createdAt = 0
    ) {
        if (empty($sessionId)) {
            throw new \InvalidArgumentException('Session ID is required');
        }
        if ($this->createdAt === 0) {
            $this->createdAt = time();
        }
    }
    
    /**
     * Add a message to the conversation
     */
    public function addMessage(Message $message): void {
        $this->messages[] = $message;
    }
    
    /**
     * Get recent conversation history
     * @return Message[]
     */
    public function getHistory(int $limit = 10): array {
        return array_slice($this->messages, -$limit);
    }
    
    /**
     * Get total message count
     */
    public function getMessageCount(): int {
        return count($this->messages);
    }
}

