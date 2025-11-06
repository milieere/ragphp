<?php

namespace App\Chat\Models;

use App\Chat\Models\ChatRole;

readonly class ChatMessage {
    public function __construct(
        public string $id,
        public string $chatId,
        public ChatRole $role,
        public string $content,
        public \DateTimeImmutable $timestamp = new \DateTimeImmutable()
    ) {}
}