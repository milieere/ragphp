<?php
declare(strict_types=1);

namespace App\Chat\Repository\Mappers;

use App\Chat\Models\Chat;

class ChatMapper {
    public static function fromRow(array $row): Chat {
        return new Chat(
            id: $row['id'],
            name: $row['name'],
            description: $row['description'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at'])
        );
    }

    public static function toRow(Chat $chat): array {
        return [
            ':id' => $chat->id,
            ':name' => $chat->name,
            ':description' => $chat->description,
            ':created_at' => $chat->createdAt->format('Y-m-d H:i:s'),
            ':updated_at' => $chat->updatedAt->format('Y-m-d H:i:s')
        ];
    }
}