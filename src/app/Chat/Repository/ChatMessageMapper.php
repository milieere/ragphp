<?php
declare(strict_types=1);

namespace App\Chat;

use App\Chat\{ChatMessage, ChatRole};

  
class ChatMessageMapper {
    public static function fromRow(array $row): ChatMessage {
        return new ChatMessage(
            id: $row['id'],
            chatId: $row['chat_id'],
            role: ChatRole::from($row['role']),
            content: $row['content'],
            timestamp: new \DateTimeImmutable($row['timestamp'])
        );
    }

    public static function toRow(ChatMessage $message, string $chatId): array {
        return [
            ':id' => $message->id,
            ':chat_id' => $chatId,
            ':role' => $message->role->name,
            ':content' => $message->content,
            ':timestamp' => $message->timestamp->format('Y-m-d H:i:s')
        ];
    }
}
