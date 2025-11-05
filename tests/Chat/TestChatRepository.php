<?php

use PHPUnit\Framework\TestCase;
use PDO\Sqlite;
use App\Chat\ChatRepository\SQLiteChatRepository;

class TestChatRepository extends TestCase{
    public function testGetChatMessages() {
        $mockDb = $this->createMock(Sqlite::class);
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockDb->method('prepare')->willReturn($mockStmt);
        $mockStmt->method('execute')->willReturn(true);

        $rows = [
            [
                'id' => '1',
                'chat_id' => 'chat1',
                'role' => 'USER',
                'content' => 'Hello!',
                'timestamp' => '2025-11-05 10:00:00'
            ],
            [
                'id' => '2',
                'chat_id' => 'chat1',
                'role' => 'BOT',
                'content' => 'Hi!',
                'timestamp' => '2025-11-05 10:01:00'
            ]
        ];

        $mockStmt->method('fetchAll')
                 ->with(PDO::FETCH_ASSOC)
                 ->willReturn($rows);
      
        $chatRepository = new ChatRepository($mockDb);
        $messages = $chatRepository->getChatMessages();
        $this->assertIsArray($messages);
    }
}
