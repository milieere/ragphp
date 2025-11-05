<?php
declare(strict_types=1);

namespace App\Chat;

use PDO\Sqlite;
use App\Chat\{ChatMessage, ChatRole};
use App\Chat\Repository\ChatMessageMapper;
use RuntimeException;


interface ChatRepositoryInterface{
    public function getChatMessages(): array;
    public function addChatMessage(ChatMessage $message): void;
}


class SQLiteChatRepository implements ChatRepositoryInterface {
    private Sqlite $db;

    public function __construct(Sqlite $db) {
        $this->db = $db;
        $this->initTables();
    }

    private function initTables(): void {
        $path = __DIR__ . '/../Shared/Database/Tables/ChatMessages.sql';
        if (!file_exists($path)) {
            throw new RuntimeException("SQL file not found at $path");
        }

        $sql = file_get_contents($path);
        $this->db->exec($sql);
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
            fn($row) => ChatMessageMapper::rowToChatMessage($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }
  
    public function addChatMessage(ChatMessage $message, string $chatId): void {
        $stmt = $this->db->prepare(
            "INSERT INTO chat_messages (id, chat_id, role, content, timestamp)
             VALUES (:id, :chat_id, :role, :content, :timestamp)"
        );

        $params = ChatMessageMapper::toRow($message, $chatId);

        if (!$stmt->execute($params)) {
            throw new RuntimeException('Failed to insert chat message');
        }
    }
}
