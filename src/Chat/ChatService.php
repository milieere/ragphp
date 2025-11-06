<?php

namespace App\Chat;

use Generator;
use InvalidArgumentException;
use DateTimeImmutable;

use App\Chat\Repository\ChatRepositoryInterface;
use App\Chat\Models\Chat;
use App\Chat\Models\ChatMessage;
use App\Chat\Models\ChatRole;
use App\Shared\Clients\Llm\LlmClientInterface;
use Psr\Log\LoggerInterface;


class ChatService {
    public function __construct(
        private ChatRepositoryInterface $chatRepo,
        private LlmClientInterface $llm,
        private LoggerInterface $logger
    ) {}

    public function chat(string $chatId, string $message): Generator {
        if (empty($chatId)) {
            throw new InvalidArgumentException('Chat ID cannot be empty');
        }
        $this->ValidateUserMessage($message);
        $chat = $this->chatRepo->getChat($chatId);
        $prompt = $this->buildPrompt($chat, $message);
        $this->chatRepo->addChatMessage(new ChatMessage(
            id: uniqid(),
            chatId: $chat->id,
            role: ChatRole::Human,
            content: $message,
            timestamp: new DateTimeImmutable()
        ));
        $this->chatRepo->updateChat($chatId);
        return $this->llm->streamCompletion($prompt);
    }

    public function getChatHistory(string $chatId): array { 
        return $this->chatRepo->getChatMessages($chatId);
    }

    public function listChats(): array {
        return $this->chatRepo->listChats();
    }

    public function getChat(string $chatId): Chat {
        return $this->chatRepo->getChat($chatId);
    }

    public function createChat(string $name, string $description): string {
        if (empty($name)) {
            throw new InvalidArgumentException('Chat name cannot be empty');
        }
        return $this->chatRepo->createChat($name, $description);
    }

    public function deleteChat(string $chatId): void {
        $this->chatRepo->deleteChat($chatId);
    }

    private function ValidateUserMessage(string $message): void {
        if (empty($message)) {
            throw new InvalidArgumentException('Message cannot be empty');
        }
        if (strlen($message) > 500) {
            throw new InvalidArgumentException('Message cannot be longer than 10000 characters');
        }
    }

    private function buildPrompt(Chat $chat, string $message): string {
        $chatMessages = $this->chatRepo->getChatMessages($chat->id);
        $chatHistory = array_map(
            fn($msg) => "{$msg->role->name}: {$msg->content}",
            $chatMessages
        );
        $chatHistory = implode(separator: "\n", array: $chatHistory);

        return "You are a helpful assistant that can answer questions and help with tasks.
        You are currently in the following chat: {$chat->name}
        The chat description is: {$chat->description}
        The chat messages are: " . $chatHistory . "
        The user message is: {$message}
        ";
    }
}