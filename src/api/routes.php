<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Psr7\Stream;

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

use App\Retrieval\{Document, InMemoryVectorRepository};
use App\Chat\{InMemoryChatRepository, ChatService};
use App\Llm\MockLlmClient;

// Initialize repositories and services
$vectorRepo = new InMemoryVectorRepository();
$chatRepo = new InMemoryChatRepository();
$llmClient = new MockLlmClient();
$chatService = new ChatService($chatRepo, $vectorRepo, $llmClient);

// Create Slim app
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// Add JSON parsing middleware
$app->addBodyParsingMiddleware();

// ====================
// Document Endpoints
// ====================

/**
 * POST /api/documents
 * Upload a new document to the knowledge base
 * 
 * Body: { "title": "...", "content": "...", "metadata": {...} }
 * Returns: { "success": true, "id": 1 }
 */
$app->post('/api/documents', function (Request $request, Response $response) use ($vectorRepo) {
    try {
        $data = $request->getParsedBody();
        
        if (!isset($data['title']) || !isset($data['content'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Title and content are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        $document = new Document(
            id: null,
            title: $data['title'],
            content: $data['content'],
            metadata: $data['metadata'] ?? []
        );
        
        $vectorRepo->add($document);
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'id' => $document->id,
            'message' => 'Document added successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (\InvalidArgumentException $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

/**
 * GET /api/documents
 * List all documents
 * 
 * Returns: { "success": true, "documents": [...], "count": 5 }
 */
$app->get('/api/documents', function (Request $request, Response $response) use ($vectorRepo) {
    $documents = $vectorRepo->getAll();
    
    $response->getBody()->write(json_encode([
        'success' => true,
        'documents' => array_map(fn($doc) => [
            'id' => $doc->id,
            'title' => $doc->title,
            'content' => substr($doc->content, 0, 200) . (strlen($doc->content) > 200 ? '...' : ''),
            'metadata' => $doc->metadata,
        ], $documents),
        'count' => count($documents)
    ]));
    
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * GET /api/documents/{id}
 * Get a specific document
 * 
 * Returns: { "success": true, "document": {...} }
 */
$app->get('/api/documents/{id}', function (Request $request, Response $response, array $args) use ($vectorRepo) {
    $id = (int) $args['id'];
    $document = $vectorRepo->get($id);
    
    if (!$document) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Document not found'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $response->getBody()->write(json_encode([
        'success' => true,
        'document' => [
            'id' => $document->id,
            'title' => $document->title,
            'content' => $document->content,
            'metadata' => $document->metadata,
        ]
    ]));
    
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * DELETE /api/documents/{id}
 * Delete a document
 * 
 * Returns: { "success": true, "message": "..." }
 */
$app->delete('/api/documents/{id}', function (Request $request, Response $response, array $args) use ($vectorRepo) {
    $id = (int) $args['id'];
    $deleted = $vectorRepo->delete($id);
    
    if (!$deleted) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Document not found'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $response->getBody()->write(json_encode([
        'success' => true,
        'message' => 'Document deleted successfully'
    ]));
    
    return $response->withHeader('Content-Type', 'application/json');
});

// ====================
// Chat Endpoints
// ====================

/**
 * POST /api/chat
 * Send a message and get streaming response with RAG
 * 
 * Body: { "session_id": "...", "message": "..." }
 * Returns: Server-Sent Events stream
 */
$app->post('/api/chat', function (Request $request, Response $response) use ($chatService) {
    try {
        $data = $request->getParsedBody();
        
        if (!isset($data['session_id']) || !isset($data['message'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'session_id and message are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        $sessionId = $data['session_id'];
        $message = $data['message'];
        
        // Create a custom stream for SSE
        $stream = fopen('php://temp', 'r+');
        
        foreach ($chatService->chat($sessionId, $message) as $chunk) {
            $event = "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
            fwrite($stream, $event);
        }
        
        // Write done event
        fwrite($stream, "data: " . json_encode(['done' => true]) . "\n\n");
        
        rewind($stream);
        
        return $response
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withBody(new Stream($stream));
            
    } catch (\InvalidArgumentException $e) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

/**
 * GET /api/conversations/{session_id}
 * Get conversation history
 * 
 * Returns: { "success": true, "messages": [...], "count": 10 }
 */
$app->get('/api/conversations/{session_id}', function (Request $request, Response $response, array $args) use ($chatRepo) {
    $sessionId = $args['session_id'];
    $messages = $chatRepo->getMessages($sessionId);
    
    $response->getBody()->write(json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'messages' => array_map(fn($msg) => [
            'id' => $msg->id,
            'role' => $msg->role,
            'content' => $msg->content,
            'sources' => $msg->sources,
            'timestamp' => $msg->timestamp,
        ], $messages),
        'count' => count($messages)
    ]));
    
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * DELETE /api/conversations/{session_id}
 * Clear conversation history
 * 
 * Returns: { "success": true, "message": "..." }
 */
$app->delete('/api/conversations/{session_id}', function (Request $request, Response $response, array $args) use ($chatRepo) {
    $sessionId = $args['session_id'];
    $deleted = $chatRepo->deleteConversation($sessionId);
    
    if (!$deleted) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Conversation not found'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $response->getBody()->write(json_encode([
        'success' => true,
        'message' => 'Conversation cleared successfully'
    ]));
    
    return $response->withHeader('Content-Type', 'application/json');
});

// ====================
// Health Check
// ====================

/**
 * GET /api/health
 * Health check endpoint
 */
$app->get('/api/health', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'status' => 'healthy',
        'timestamp' => time()
    ]));
    
    return $response->withHeader('Content-Type', 'application/json');
});

return $app;

