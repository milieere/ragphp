<?php
declare(strict_types=1);

namespace App\Chat\Repository;

use RuntimeException;
use PDO;

use App\Chat\Models\{Chat, ChatMessage};
use App\Chat\Repository\Mappers\ChatMessageMapper;
use App\Chat\Repository\Mappers\ChatMapper;
use App\Chat\Repository\ChatRepositoryInterface;


class SQLiteChatRepository implements ChatRepositoryInterface {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->initTables();
    }

    private function initTables(): void {
        $table_paths = [
            __DIR__ . '/../../Shared/Database/Tables/ChatMessages.sql',
            __DIR__ . '/../../Shared/Database/Tables/Chats.sql',
        ];
        foreach ($table_paths as $path) {
            if (!file_exists($path)) {
                throw new RuntimeException("SQL file not found at $path");
            }
            $sql = file_get_contents($path);
            $this->db->exec($sql);
        }
    }

    public function createChat(string $name, string $description): string {
        $chat_id = uniqid();
        $chat = new Chat(
            id: $chat_id,
            name: $name,
            description: $description,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );
        $stmt = $this->db->prepare(
            "INSERT INTO chats (id, name, description, created_at, updated_at)
             VALUES (:id, :name, :description, :created_at, :updated_at)"
        );
        $params = ChatMapper::toRow($chat);
        $stmt->execute($params);
        return $chat_id;
    }
  
    public function getChat(string $chatId): Chat {
        $stmt = $this->db->prepare(
            "SELECT id, name, description, created_at, updated_at
             FROM chats
             WHERE id = :id"
        );
        $stmt->execute([':id' => $chatId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException("Chat not found");
        }
        return ChatMapper::fromRow($row);
    }

    public function updateChat(string $chatId): void {
        $timestamp = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "UPDATE chats
             SET updated_at = :updated_at
             WHERE id = :id"
        );
        if (!$stmt->execute([':updated_at' => $timestamp, ':id' => $chatId])) {
            throw new RuntimeException('Failed to update chat');
        }
    }

    public function deleteChat(string $chatId): void {
        $stmt = $this->db->prepare(
            "DELETE FROM chats
             WHERE id = :id"
        );
        if (!$stmt->execute([':id' => $chatId])) {
            throw new RuntimeException('Failed to delete chat');
        }
    }

    public function listChats(): array {
        $stmt = $this->db->prepare(
            "SELECT id, name, description, created_at, updated_at
             FROM chats
             ORDER BY updated_at DESC"
        );
        if (!$stmt->execute()) {
            throw new RuntimeException('Failed to list chats');
        }
        return array_map(
            fn($row): Chat => ChatMapper::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function getChatMessages(string $chatId): array {
        $stmt = $this->db->prepare(
            "SELECT id, chat_id, role, content, timestamp
             FROM chat_messages
             WHERE chat_id = :id
             ORDER BY timestamp ASC"
        );
        
        if (!$stmt->execute([':id' => $chatId])) {
            throw new RuntimeException('Failed to fetch chat messages');
        }
  
        return array_map(
            fn($row): ChatMessage => ChatMessageMapper::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }
  
    public function addChatMessage(ChatMessage $message): void {
        $stmt = $this->db->prepare(
            "INSERT INTO chat_messages (id, chat_id, role, content, timestamp)
             VALUES (:id, :chat_id, :role, :content, :timestamp)"
        );

        $params = ChatMessageMapper::toRow($message);

        if (!$stmt->execute($params)) {
            throw new RuntimeException('Failed to insert chat message');
        }
    }
}
