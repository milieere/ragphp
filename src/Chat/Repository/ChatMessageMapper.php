<?php
declare(strict_types=1);

namespace App\Chat\Repository;

use App\Chat\Models\{ChatMessage, ChatRole};

  
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

    public static function toRow(ChatMessage $message): array {
        return [
            ':id' => $message->id,
            ':chat_id' => $message->chatId,
            ':role' => $message->role->name,
            ':content' => $message->content,
            ':timestamp' => $message->timestamp->format('Y-m-d H:i:s')
        ];
    }
}
