<?php

use DI\ContainerBuilder;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;
use App\Retrieval\{VectorRepositoryInterface, InMemoryVectorRepository};
use App\Chat\{ChatRepositoryInterface, InMemoryChatRepository, ChatService};
use App\Llm\{LlmClientInterface, MockLlmClient, OpenAiClient};

// Composer autoloader - automatically loads all classes based on namespace
require_once __DIR__ . '/../../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    // Logger configuration
    LoggerInterface::class => function(): Logger {
        $logger = new Logger('api');
        
        // Console handler - outputs to stdout (you'll see this in terminal)
        $streamHandler = new StreamHandler('php://stdout', Logger::DEBUG);
        
        // Custom format for better readability
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context%\n",
            "Y-m-d H:i:s",
            true,
            true
        );
        $streamHandler->setFormatter($formatter);
        
        $logger->pushHandler($streamHandler);
        
        return $logger;
    },
    
    // Bind interfaces to concrete implementations
    VectorRepositoryInterface::class => DI\create(InMemoryVectorRepository::class),
    
    ChatRepositoryInterface::class => DI\create(InMemoryChatRepository::class),
    
    LlmClientInterface::class => DI\create(MockLlmClient::class),
    // Uncomment below to use OpenAI instead of mock:
    // LlmClientInterface::class => DI\create(OpenAiClient::class),
    
    // ChatService with autowired dependencies
    ChatService::class => DI\autowire()
        ->constructor(
            DI\get(ChatRepositoryInterface::class),
            DI\get(VectorRepositoryInterface::class),
            DI\get(LlmClientInterface::class),
            DI\get(LoggerInterface::class)
        ),
]);

return $containerBuilder->build();
