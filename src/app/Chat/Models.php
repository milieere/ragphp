<?php

namespace App\Chat;

enum ChatRole
{
    case Bot;
    case Human;
}

readonly class ChatMessage {
    public function __construct(
        public string $id,
        public string $chatId,
        public ChatRole $role,
        public string $content,
        public ?\DateTimeImmutable $timestamp = null
    ) {
        $this->timestamp ??= new \DateTimeImmutable();
    }
}