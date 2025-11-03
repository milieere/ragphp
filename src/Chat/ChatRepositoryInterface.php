<?php

namespace App\Chat;

/**
 * Interface for conversation storage
 * Can be implemented with in-memory, Redis, PostgreSQL, etc.
 */
interface ChatRepositoryInterface {
    /**
     * Get a conversation by session ID
     */
    public function getConversation(string $sessionId): ?Conversation;
    
    /**
     * Save or update a conversation
     */
    public function saveConversation(Conversation $conversation): void;
    
    /**
     * Add a message to a conversation (creates conversation if needed)
     */
    public function addMessage(string $sessionId, Message $message): void;
    
    /**
     * Get messages for a conversation
     * @return Message[]
     */
    public function getMessages(string $sessionId, int $limit = 50): array;
    
    /**
     * Delete a conversation
     */
    public function deleteConversation(string $sessionId): bool;
}

