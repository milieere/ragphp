<?php

use PHPUnit\Framework\TestCase;
use PDO\Sqlite;
use App\Chat\Repository\SQLiteChatRepository;
use App\Chat\Models\ChatMessage;

class TestChatRepository extends TestCase{
    public function testGetChatMessages() {
        $mockDb = $this->createMock(Sqlite::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        
        // Mock exec() to prevent initTables() from running
        $mockDb->method('exec')->willReturn(1);
        
        $mockDb->method('prepare')->willReturn($mockStmt);
        $mockStmt->method('execute')->willReturn(true);

        $rows = [
            [
                'id' => '1',
                'chat_id' => 'chat1',
                'role' => 'Human',
                'content' => 'Hello!',
                'timestamp' => '2025-11-05 10:00:00'
            ],
            [
                'id' => '2',
                'chat_id' => 'chat1',
                'role' => 'Bot',
                'content' => 'Hi!',
                'timestamp' => '2025-11-05 10:01:00'
            ]
        ];

        $mockStmt->method('fetchAll')
                 ->with(\PDO::FETCH_ASSOC)
                 ->willReturn($rows);
      
        $chatRepository = new SQLiteChatRepository($mockDb);
        $messages = $chatRepository->getChatMessages('chat1');
        
        $this->assertIsArray($messages);
        $this->assertCount(2, $messages);
        $this->assertInstanceOf(ChatMessage::class, $messages[0]);
        $this->assertEquals('Hello!', $messages[0]->content);
    }
}
