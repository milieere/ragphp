<?php

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;

use App\Chat\ChatService;
use App\Shared\Clients\Llm\LlmClientInterface;
use App\Chat\Repository\ChatRepositoryInterface;
use App\Chat\Models\ChatMessage;
use App\Chat\Models\ChatRole;
use App\Chat\Models\Chat;

class TestChat extends TestCase {
    public function testChat() {
        function streamCompletion(): Generator {
            yield 'Test response';
        }
        $mockLlm = $this->createMock(LlmClientInterface::class);
        $mockChatRepo = $this->createMock(ChatRepositoryInterface::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        $mockLlm->method('streamCompletion')->willReturn(streamCompletion());
        $mockChatRepo->method('getChat')->willReturn(new Chat('1', 'Test Chat', 'Test Description', new DateTimeImmutable(), new DateTimeImmutable()));
        $mockChatRepo->method('addChatMessage')->willReturn(new ChatMessage('1', '1', ChatRole::Human, 'Hello, how are you?', new DateTimeImmutable()));
        $mockChatRepo->method('updateChat')->willReturn(new Chat('1', 'Test Chat', 'Test Description', new DateTimeImmutable(), new DateTimeImmutable()));
        $chatService = new ChatService();
        $chatService->chat('1', 'Hello, how are you?');
    }
}