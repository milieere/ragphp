<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Chat\ChatService;

// Load DI container
$container = require __DIR__ . '/container.php';

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();

// ====================
// API Endpoints
// ====================

// List all chats
$app->get('/api/chats', function (Request $request, Response $response) {
    $chatService = $this->get(ChatService::class);
    $chats = $chatService->listChats();
    
    $response->getBody()->write(json_encode($chats));
    return $response->withHeader('Content-Type', 'application/json');
});

// Get single chat
$app->get('/api/chats/{chatId}', function (Request $request, Response $response, array $args) {
    $chatService = $this->get(ChatService::class);
    $chat = $chatService->getChat($args['chatId']);
    
    $response->getBody()->write(json_encode($chat));
    return $response->withHeader('Content-Type', 'application/json');
});

// Create new chat
$app->post('/api/chats', function (Request $request, Response $response) {
    $chatService = $this->get(ChatService::class);
    $body = $request->getParsedBody();
    
    $name = $body['name'] ?? 'New Chat';
    $description = $body['description'] ?? '';
    
    $chatId = $chatService->createChat($name, $description);
    
    $response->getBody()->write(json_encode(['chatId' => $chatId]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

// Delete chat
$app->delete('/api/chats/{chatId}', function (Request $request, Response $response, array $args) {
    $chatService = $this->get(ChatService::class);
    $chatService->deleteChat($args['chatId']);
    
    return $response->withStatus(204);
});

// Get chat messages
$app->get('/api/chats/{chatId}/messages', function (Request $request, Response $response, array $args) {
    $chatService = $this->get(ChatService::class);
    $messages = $chatService->getChatHistory($args['chatId']);
    
    $response->getBody()->write(json_encode($messages));
    return $response->withHeader('Content-Type', 'application/json');
});

// Send message (streaming)
$app->post('/api/chats/{chatId}/messages', function (Request $request, Response $response, array $args) use ($container) {
    $chatService = $container->get(ChatService::class);
    $body = $request->getParsedBody();
    $message = $body['message'] ?? '';
    
    // Set headers for Server-Sent Events
    $response = $response
        ->withHeader('Content-Type', 'text/event-stream')
        ->withHeader('Cache-Control', 'no-cache')
        ->withHeader('Connection', 'keep-alive')
        ->withHeader('X-Accel-Buffering', 'no');
    
    try {
        // Get streaming generator
        $stream = $chatService->chat($args['chatId'], $message);
        
        // Collect full response to save later
        $fullResponse = '';
        
        // Stream chunks
        foreach ($stream as $chunk) {
            $fullResponse .= $chunk . ' ';
            echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
            
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
        
        // Save assistant's response
        $chatRepo = $container->get(\App\Chat\Repository\ChatRepositoryInterface::class);
        $chatRepo->addChatMessage(new \App\Chat\Models\ChatMessage(
            id: uniqid(),
            chatId: $args['chatId'],
            role: \App\Chat\Models\ChatRole::Bot,
            content: trim($fullResponse),
            timestamp: new \DateTimeImmutable()
        ));
        
        // Send completion signal
        echo "data: [DONE]\n\n";
        
    } catch (\Exception $e) {
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    }
    
    return $response;
});

return $app;

