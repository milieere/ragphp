<?php

use DI\ContainerBuilder;
use App\Retrieval\{VectorRepositoryInterface, InMemoryVectorRepository};
use App\Chat\{ChatRepositoryInterface, InMemoryChatRepository, ChatService};
use App\Llm\{LlmClientInterface, MockLlmClient, OpenAiClient};

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Retrieval/Models.php';
require_once __DIR__ . '/../Retrieval/VectorRepositoryInterface.php';
require_once __DIR__ . '/../Retrieval/InMemoryVectorRepository.php';
require_once __DIR__ . '/../Chat/Models.php';
require_once __DIR__ . '/../Chat/ChatRepositoryInterface.php';
require_once __DIR__ . '/../Chat/InMemoryChatRepository.php';
require_once __DIR__ . '/../Chat/ChatService.php';
require_once __DIR__ . '/../Llm/LlmClientInterface.php';
require_once __DIR__ . '/../Llm/MockLlmClient.php';
require_once __DIR__ . '/../Llm/OpenAiClient.php';

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
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
            DI\get(LlmClientInterface::class)
        ),
]);

return $containerBuilder->build();
