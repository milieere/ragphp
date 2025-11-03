# RAG Chatbot - Architecture Documentation

## 🎯 Design Philosophy

This RAG chatbot backend demonstrates **clean architecture** and **separation of concerns** through a **module-based** design. Each module is self-contained with its own models, interfaces, and implementations.

## 📦 Module Organization

### **1. Retrieval Module** (`src/Retrieval/`)
**Purpose**: Document storage and semantic search

```
Retrieval/
├── Models.php                      # Document, SearchResult
├── VectorRepositoryInterface.php   # Storage contract
└── InMemoryVectorRepository.php    # Simple keyword search implementation
```

**Responsibilities**:
- Store and retrieve documents
- Perform relevance search (keyword-based for MVP)
- Maintain document metadata

**Key Abstractions**:
- `Document`: Immutable entity with validation
- `SearchResult`: Value object containing document + relevance score
- `VectorRepositoryInterface`: Allows swapping storage implementations

**Future Extensions**:
```php
// Easy to add vector search later
class PineconeVectorRepository implements VectorRepositoryInterface {
    public function search(string $query, int $limit = 3): array {
        // Use actual vector embeddings
    }
}
```

---

### **2. Chat Module** (`src/Chat/`)
**Purpose**: Conversation management and RAG orchestration

```
Chat/
├── Models.php                      # Message, Conversation
├── ChatRepositoryInterface.php     # Chat storage contract
├── InMemoryChatRepository.php      # In-memory implementation
└── ChatService.php                 # RAG orchestration logic
```

**Responsibilities**:
- Manage conversation state
- Orchestrate RAG flow (retrieve → augment → generate)
- Save conversation history with metadata

**RAG Flow** (in `ChatService`):
1. **Retrieve**: Search relevant documents using `VectorRepository`
2. **Augment**: Build prompt with context + history
3. **Generate**: Stream LLM response
4. **Store**: Save user message + assistant response with sources

**Key Design**:
```php
class ChatService {
    public function __construct(
        private ChatRepositoryInterface $chatRepo,      // Conversation storage
        private VectorRepositoryInterface $vectorRepo,  // Document search
        private LlmClientInterface $llm                 // LLM client
    ) {}
    
    public function chat(string $sessionId, string $message): Generator {
        // RAG orchestration happens here
    }
}
```

---

### **3. LLM Module** (`src/Llm/`)
**Purpose**: Language model integration

```
Llm/
├── LlmClientInterface.php     # LLM client contract
├── MockLlmClient.php          # Mock implementation for testing
└── OpenAiClient.php           # OpenAI with streaming support
```

**Responsibilities**:
- Abstract LLM provider details
- Handle streaming responses
- Manage API communication

**Streaming Design**:
```php
interface LlmClientInterface {
    public function streamCompletion(string $prompt): Generator;
}
```

Using PHP generators allows efficient memory usage and real-time streaming without buffering the entire response.

---

### **4. API Layer** (`src/api/`)
**Purpose**: HTTP interface using Slim Framework

```
api/
└── routes.php    # All REST endpoints + dependency wiring
```

**Endpoints**:

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/documents` | Upload document |
| GET | `/api/documents` | List all documents |
| GET | `/api/documents/{id}` | Get specific document |
| DELETE | `/api/documents/{id}` | Delete document |
| POST | `/api/chat` | Send message (streaming) |
| GET | `/api/conversations/{id}` | Get history |
| DELETE | `/api/conversations/{id}` | Clear conversation |
| GET | `/api/health` | Health check |

**Dependency Wiring**:
```php
$vectorRepo = new InMemoryVectorRepository();
$chatRepo = new InMemoryChatRepository();
$llmClient = new MockLlmClient();
$chatService = new ChatService($chatRepo, $vectorRepo, $llmClient);
```

This makes it trivial to swap implementations:
```php
// Production setup
$vectorRepo = new PineconeVectorRepository($apiKey);
$chatRepo = new RedisChatRepository($redisClient);
$llmClient = new OpenAiClient($openaiKey);
```

---

## 🏛️ SOLID Principles

### **Single Responsibility Principle**
- Each class has one clear purpose
- `Document` only represents document data
- `ChatService` only orchestrates RAG flow
- `InMemoryVectorRepository` only handles storage

### **Open/Closed Principle**
- Open for extension via interfaces
- Closed for modification (swap implementations without changing core logic)

### **Liskov Substitution Principle**
- Any `VectorRepositoryInterface` implementation can replace another
- Any `LlmClientInterface` can be used interchangeably

### **Interface Segregation Principle**
- Interfaces are minimal and focused
- No "god interfaces" with unnecessary methods

### **Dependency Inversion Principle**
- High-level modules (ChatService) depend on abstractions (interfaces)
- Not on concrete implementations (InMemoryVectorRepository)

---

## 🔄 Data Flow

### **Document Upload**
```
Client → POST /api/documents
  → routes.php
    → InMemoryVectorRepository::add()
      → Document (validation)
        → Stored in memory
```

### **Chat with RAG**
```
Client → POST /api/chat
  → routes.php
    → ChatService::chat()
      → VectorRepository::search()  [Retrieve relevant docs]
      → ChatRepository::getMessages()  [Get history]
      → buildPrompt()  [Augment with context]
      → LlmClient::streamCompletion()  [Generate response]
        → yield chunks  [Stream to client]
      → ChatRepository::addMessage()  [Save conversation]
```

---

## 🚀 Lambda Deployment Strategy

### **Why This Architecture Works for Lambda**

1. **Stateless Design**
   - Each request is self-contained
   - No global state required
   - Easy to scale horizontally

2. **Repository Pattern**
   - Swap in-memory storage for DynamoDB/Aurora
   - No code changes in service layer

3. **Streaming Support**
   - Uses PHP generators (memory efficient)
   - Works with Lambda response streaming
   - No buffering required

### **Deployment Setup**

```php
// Lambda entry point (public/index.php)
$app = require __DIR__ . '/../src/api/routes.php';
$app->run();
```

**Lambda Configuration**:
```yaml
functions:
  chat:
    handler: public/index.php
    timeout: 300  # 5 min for streaming
    memorySize: 1024
    environment:
      OPENAI_API_KEY: ${env:OPENAI_API_KEY}
    layers:
      - ${bref:layer.php-81-fpm}
```

### **Production Repositories**

```php
// Replace in routes.php for production
$vectorRepo = new DynamoDBVectorRepository(
    new DynamoDbClient([...])
);

$chatRepo = new RedisChatRepository(
    new Redis(['host' => getenv('REDIS_HOST')])
);

$llmClient = new OpenAiClient(
    getenv('OPENAI_API_KEY')
);
```

---

## 🧪 Testing Strategy

### **Unit Testing**
Each module can be tested independently:

```php
$mockLlm = new MockLlmClient();
$mockVectorRepo = new InMemoryVectorRepository();
$mockChatRepo = new InMemoryChatRepository();

$chatService = new ChatService($mockChatRepo, $mockVectorRepo, $mockLlm);

// Test RAG flow
$result = '';
foreach ($chatService->chat('test-session', 'test message') as $chunk) {
    $result .= $chunk;
}

assert($result !== '');
```

### **Integration Testing**
Test with real implementations:

```php
$vectorRepo = new InMemoryVectorRepository();
$vectorRepo->add(new Document(null, 'Test', 'Content...'));

$results = $vectorRepo->search('test query');
assert(count($results) > 0);
```

---

## 📈 Performance Considerations

### **Search Performance**
Current: O(n) keyword matching
- Acceptable for < 1000 documents
- For production: Use vector embeddings with approximate nearest neighbor search

### **Memory Usage**
- In-memory storage: 🚨 Not production-ready
- Streams responses: ✅ Memory efficient
- PHP generators: ✅ Lazy evaluation

### **Latency**
1. Document retrieval: ~1-5ms (in-memory)
2. LLM streaming: ~500ms first token, then continuous
3. Total: ~500-700ms for first response chunk

---

## 🔐 Security Considerations

### **Input Validation**
- All models validate in constructors
- Type hints enforce contracts
- Max length checks prevent abuse

### **API Security** (TODO for production)
- Add authentication middleware
- Rate limiting per session
- Input sanitization
- CORS configuration

### **Data Privacy**
- Conversation history stored per session
- Easy to implement TTL for auto-deletion
- No sensitive data logged

---

## 🎓 Learning Points

### **What This Demonstrates**

1. **Clean Architecture**
   - Domain models separate from infrastructure
   - Business logic isolated from HTTP concerns
   - Clear boundaries between modules

2. **Dependency Injection**
   - Constructor injection everywhere
   - No global state or singletons
   - Easy to test and modify

3. **Interface-Based Design**
   - Code to interfaces, not implementations
   - Swap implementations without changing consumers
   - Future-proof architecture

4. **PHP 8 Features**
   - Constructor property promotion
   - Named arguments
   - Readonly properties
   - Generators for streaming

5. **Simple Yet Extensible**
   - Minimal complexity
   - Clear extension points
   - Easy to understand and modify

---

## 🚧 Production Roadmap

### **Immediate Improvements**
- [ ] Add authentication middleware
- [ ] Implement rate limiting
- [ ] Add request validation middleware
- [ ] Error handling improvements
- [ ] Logging infrastructure

### **Storage Upgrades**
- [ ] PostgreSQL + pgvector for semantic search
- [ ] Redis for conversation history
- [ ] S3 for document storage
- [ ] DynamoDB for metadata

### **Features**
- [ ] Document chunking for large texts
- [ ] Multi-turn conversation context window
- [ ] Source citation in responses
- [ ] Document versioning
- [ ] Conversation export

### **Observability**
- [ ] Structured logging
- [ ] Metrics (latency, throughput)
- [ ] Distributed tracing
- [ ] Error tracking

---

## 📚 References

- **RAG**: [Retrieval-Augmented Generation Paper](https://arxiv.org/abs/2005.11401)
- **Clean Architecture**: Robert C. Martin
- **SOLID Principles**: Robert C. Martin
- **PHP Best Practices**: [PHP-FIG PSR Standards](https://www.php-fig.org/)
- **Slim Framework**: [Slim Documentation](https://www.slimframework.com/)

---

**Built with ❤️ following clean code principles**



