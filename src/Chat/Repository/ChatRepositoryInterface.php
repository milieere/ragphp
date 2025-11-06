<?php
declare(strict_types=1);

namespace App\Chat\Repository;

use App\Chat\Models\Chat;
use App\Chat\Models\ChatMessage;

interface ChatRepositoryInterface {
    public function createChat(string $name, string $description): string;
    public function getChat(string $chatId): Chat;
    public function updateChat(string $chatId): void;
    public function deleteChat(string $chatId): void;
    public function listChats(): array;
    public function getChatMessages(string $chatId): array;
    public function addChatMessage(ChatMessage $message): void;
}
