<?php
declare(strict_types=1);

namespace App\Chat;

use PDO\Sqlite;
use App\Chat\{ChatMessage, ChatRole};

interface ChatRepositoryInterface{
    public function getChatMessages(): array;
    public function addChatMessage(ChatMessage $message): void;
}

class SQLiteChatRepository implements ChatRepositoryInterface {
    private Sqlite $db;

    public function __construct(string $path = 'chatMessages.db') {
        $this->db = new Sqlite("$path");
        $this->initTables();
    }

    private function initTables(): void {
        $init_chat_messages_table_sql = file_get_contents(__DIR__ . '/../Shared/Database/Tables/ChatMessages.sql');
        $this->db->exec($init_chat_messages_table_sql);
    }
  
   /**
   * @param string $chatId
   * @return ChatMessage[]  Array of ChatMessage objects
   */
    public function getChatMessages(string $chatId): array {
        $stmt = $this->db->prepare("SELECT * FROM chat_messages WHERE chat_id = :id ORDER BY timestamp ASC");
        $stmt->execute([':id' => $chatId]);
        return array_map(fn($r) => new ChatMessage(
            id: $r['id'],
            chatId: $r['chat_id'],
            role: ChatRole::from($r['role']),
            content: $r['content'],
            timestamp: new \DateTimeImmutable($r['timestamp']),
        ), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function addChatMessage(ChatMessage $message, string $chatId): void {
        $stmt = $this->db->prepare(
            "INSERT INTO chat_messages (id, chat_id, role, content, timestamp) 
             VALUES (:id, :chat_id, :role, :content, :timestamp)"
        );
        $stmt->execute([
            ':id' => $message->id,
            ':chat_id' => $chatId,
            ':role' => $message->role->name,
            ':content' => $message->content,
            ':timestamp' => $message->timestamp->format('Y-m-d H:i:s')
        ]);
    }
}
