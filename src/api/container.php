<?php

use DI\ContainerBuilder;

use Psr\Log\LoggerInterface;
use App\Chat\Repository\ChatRepositoryInterface;

use App\Chat\Models\ChatMessage;
use App\Chat\Models\ChatRole;
use App\Chat\Repository\ChatMessageMapper;
use App\Chat\Repository\SQLiteChatRepository;

use App\Shared\Utilities\ResponseHelper;



// Composer autoloader - automatically loads all classes based on namespace
require_once __DIR__ . '/../../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    LoggerInterface::class => require __DIR__ . '/../Shared/Utilities/logger.php',

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
