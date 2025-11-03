# Dependency Injection Setup

This project now uses **PHP-DI** for dependency injection, making the codebase more testable, maintainable, and following SOLID principles.

## What Changed?

### Before (Direct Instantiation)
```php
$vectorRepo = new InMemoryVectorRepository();
$chatRepo = new InMemoryChatRepository();
$llmClient = new MockLlmClient();
$chatService = new ChatService($chatRepo, $vectorRepo, $llmClient);

$app->post('/api/documents', function (Request $request, Response $response) use ($vectorRepo) {
    // ...
});
```

### After (Dependency Injection)
```php
// Container configured in container.php
$container = require __DIR__ . '/container.php';
AppFactory::setContainer($container);

$app->post('/api/documents', function (Request $request, Response $response) {
    $vectorRepo = $this->get(VectorRepositoryInterface::class);
    // ...
});
```

## Benefits

1. **Interface-based Programming**: Routes depend on interfaces, not concrete implementations
2. **Easy Testing**: Mock dependencies by binding test implementations in the container
3. **Flexible Configuration**: Switch implementations without changing route code
4. **Single Responsibility**: Container handles object creation and dependency resolution
5. **Centralized Configuration**: All dependencies configured in one place (`container.php`)

## Container Configuration

The container is defined in `src/api/container.php`:

```php
$containerBuilder->addDefinitions([
    // Interface → Implementation bindings
    VectorRepositoryInterface::class => DI\create(InMemoryVectorRepository::class),
    ChatRepositoryInterface::class => DI\create(InMemoryChatRepository::class),
    LlmClientInterface::class => DI\create(MockLlmClient::class),
    
    // Autowired service with dependencies
    ChatService::class => DI\autowire()
        ->constructor(
            DI\get(ChatRepositoryInterface::class),
            DI\get(VectorRepositoryInterface::class),
            DI\get(LlmClientInterface::class)
        ),
]);
```

## Installation

Install the PHP-DI dependency:

```bash
composer install
```

Or if you need to update:

```bash
composer update
```

## Switching Implementations

To switch from MockLlmClient to OpenAiClient, just update `container.php`:

```php
// Before
LlmClientInterface::class => DI\create(MockLlmClient::class),

// After
LlmClientInterface::class => DI\create(OpenAiClient::class),
```

No need to change any route code!

## Testing with DI

When writing tests, you can create a test container with mock implementations:

```php
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    VectorRepositoryInterface::class => new MockVectorRepository(),
    ChatRepositoryInterface::class => new MockChatRepository(),
    // ...
]);
$testContainer = $containerBuilder->build();
```

## Available Dependencies

The following dependencies are configured in the container:

- `VectorRepositoryInterface` → `InMemoryVectorRepository`
- `ChatRepositoryInterface` → `InMemoryChatRepository`
- `LlmClientInterface` → `MockLlmClient` (can switch to `OpenAiClient`)
- `ChatService` → Autowired with all dependencies

## Usage in Routes

To use a dependency in a route handler:

```php
$app->get('/api/example', function (Request $request, Response $response) {
    // Get dependency from container using interface
    $repository = $this->get(SomeRepositoryInterface::class);
    $service = $this->get(SomeService::class);
    
    // Use the dependencies
    $data = $repository->getData();
    $result = $service->process($data);
    
    // ...
});
```

## Further Reading

- [PHP-DI Documentation](https://php-di.org/)
- [Slim Framework with PHP-DI](https://php-di.org/doc/frameworks/slim.html)
- [Dependency Injection Principles](https://en.wikipedia.org/wiki/Dependency_injection)

