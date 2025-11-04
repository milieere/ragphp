<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Stream;
use App\Retrieval\{Document, VectorRepositoryInterface};
use App\Chat\{ChatRepositoryInterface, ChatService};

// Load DI container
$container = require __DIR__ . '/container.php';

// Create Slim app with DI container
AppFactory::setContainer($container);
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
$app->post('/api/documents', function (Request $request, Response $response) {
    $logger = $this->get(LoggerInterface::class);
    $vectorRepo = $this->get(VectorRepositoryInterface::class);
    
    $logger->info('📥 POST /api/documents - Request received');
    
    try {
        $data = $request->getParsedBody();
        $logger->debug('Request data', ['title' => $data['title'] ?? 'N/A', 'content_length' => strlen($data['content'] ?? '')]);
        
        if (!isset($data['title']) || !isset($data['content'])) {
            $logger->warning('⚠️  Validation failed: Missing title or content');
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
        $logger->info("✅ Document created successfully", ['id' => $document->id, 'title' => $document->title]);
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'id' => $document->id,
            'message' => 'Document added successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (\InvalidArgumentException $e) {
        $logger->error('❌ Document creation failed', ['error' => $e->getMessage()]);
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
$app->get('/api/documents', function (Request $request, Response $response) {
    $logger = $this->get(LoggerInterface::class);
    $vectorRepo = $this->get(VectorRepositoryInterface::class);
    
    $logger->info('📋 GET /api/documents - Fetching all documents');
    $documents = $vectorRepo->getAll();
    $logger->debug('Retrieved documents', ['count' => count($documents)]);
    
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
$app->get('/api/documents/{id}', function (Request $request, Response $response, array $args) {
    $vectorRepo = $this->get(VectorRepositoryInterface::class);
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
$app->delete('/api/documents/{id}', function (Request $request, Response $response, array $args) {
    $vectorRepo = $this->get(VectorRepositoryInterface::class);
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
$app->post('/api/chat', function (Request $request, Response $response) {
    $logger = $this->get(LoggerInterface::class);
    $chatService = $this->get(ChatService::class);
    
    $logger->info('💬 POST /api/chat - Chat request received');
    
    try {
        $data = $request->getParsedBody();
        $logger->debug('Chat request', ['session_id' => $data['session_id'] ?? 'N/A', 'message' => substr($data['message'] ?? '', 0, 50) . '...']);
        
        if (!isset($data['session_id']) || !isset($data['message'])) {
            $logger->warning('⚠️  Chat validation failed: Missing session_id or message');
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'session_id and message are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        $sessionId = $data['session_id'];
        $message = $data['message'];
        
        $logger->info('🚀 Starting chat processing', ['session_id' => $sessionId]);
        
        // Create a custom stream for SSE
        $stream = fopen('php://temp', 'r+');
        
        $chunkCount = 0;
        foreach ($chatService->chat($sessionId, $message) as $chunk) {
            $event = "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
            fwrite($stream, $event);
            $chunkCount++;
        }
        
        $logger->info('✅ Chat completed successfully', ['session_id' => $sessionId, 'chunks_streamed' => $chunkCount]);
        
        // Write done event
        fwrite($stream, "data: " . json_encode(['done' => true]) . "\n\n");
        
        rewind($stream);
        
        return $response
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withBody(new Stream($stream));
            
    } catch (\InvalidArgumentException $e) {
        $logger->error('❌ Chat failed', ['error' => $e->getMessage(), 'session_id' => $data['session_id'] ?? 'N/A']);
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
$app->get('/api/conversations/{session_id}', function (Request $request, Response $response, array $args) {
    $chatRepo = $this->get(ChatRepositoryInterface::class);
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
$app->delete('/api/conversations/{session_id}', function (Request $request, Response $response, array $args) {
    $chatRepo = $this->get(ChatRepositoryInterface::class);
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
    $logger = $this->get(LoggerInterface::class);
    $logger->info('❤️  Health check requested');
    
    $response->getBody()->write(json_encode([
        'status' => 'healthy',
        'timestamp' => time()
    ]));
    
    return $response->withHeader('Content-Type', 'application/json');
});

return $app;

