<?php

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use App\Chat\Repository\SQLiteChatRepository;
use App\Chat\Repository\ChatRepositoryInterface;
use App\Chat\ChatService;
use App\Shared\Clients\Llm\LlmClientInterface;
use App\Shared\Clients\Llm\MockLlmClient;


$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    // Logger
    LoggerInterface::class => function() {
        $loggerFactory = require __DIR__ . '/../Shared/Utilities/logger.php';
        return $loggerFactory();
    },
    
    // Database (SQLite)
    PDO::class => function() {
        $db = new PDO('sqlite:' . __DIR__ . '/../../data/chat.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    },
    
    // Repository
    ChatRepositoryInterface::class => function(ContainerInterface $c) {
        return new SQLiteChatRepository($c->get(PDO::class));
    },
    
    // LLM Client
    LlmClientInterface::class => function() {
        return new MockLlmClient('mock-api-key');
    },
    
    // Chat Service
    ChatService::class => function(ContainerInterface $c) {
        return new ChatService(
            $c->get(ChatRepositoryInterface::class),
            $c->get(LlmClientInterface::class),
            $c->get(LoggerInterface::class)
        );
    },
]);

return $containerBuilder->build();

