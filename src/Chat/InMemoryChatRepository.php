<?php

namespace App\Chat;

/**
 * Simple in-memory chat repository
 * In production, replace with Redis or PostgreSQL for persistence
 */
class InMemoryChatRepository implements ChatRepositoryInterface {
    private array $conversations = [];
    private int $nextMessageId = 1;
    
    public function getConversation(string $sessionId): ?Conversation {
        return $this->conversations[$sessionId] ?? null;
    }
    
    public function saveConversation(Conversation $conversation): void {
        $this->conversations[$conversation->sessionId] = $conversation;
    }
    
    public function addMessage(string $sessionId, Message $message): void {
        $conversation = $this->getConversation($sessionId);
        if (!$conversation) {
            $conversation = new Conversation($sessionId);
            $this->saveConversation($conversation);
        }
        
        // Auto-assign message ID if not set
        if (!$message->id) {
            $message->id = $this->nextMessageId++;
        }
        
        $conversation->addMessage($message);
    }
    
    public function getMessages(string $sessionId, int $limit = 50): array {
        $conversation = $this->getConversation($sessionId);
        if (!$conversation) {
            return [];
        }
        return $conversation->getHistory($limit);
    }
    
    public function deleteConversation(string $sessionId): bool {
        if (isset($this->conversations[$sessionId])) {
            unset($this->conversations[$sessionId]);
            return true;
        }
        return false;
    }
}

