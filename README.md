# RAG Chatbot Backend

A clean, modular RAG (Retrieval-Augmented Generation) chatbot backend built with PHP, following best practices for OOP architecture and separation of concerns.

## 🏗️ Architecture

The application is organized into **three main modules**, each with clear responsibilities:

### **1. Retrieval Module** (`src/Retrieval/`)
Handles document storage and semantic search.
- `Models.php` - Document and SearchResult entities
- `VectorRepositoryInterface.php` - Storage abstraction
- `InMemoryVectorRepository.php` - In-memory implementation with keyword search

### **2. Chat Module** (`src/Chat/`)
Manages conversations and orchestrates RAG flow.
- `Models.php` - Message and Conversation entities
- `ChatRepositoryInterface.php` - Conversation storage abstraction
- `InMemoryChatRepository.php` - In-memory implementation
- `ChatService.php` - Main RAG orchestration logic

### **3. LLM Module** (`src/Llm/`)
Integrates with language models.
- `LlmClientInterface.php` - LLM client abstraction
- `MockLlmClient.php` - Mock implementation for testing
- `OpenAiClient.php` - OpenAI integration with streaming

### **4. API Layer** (`src/api/`)
HTTP endpoints using Slim Framework.
- `routes.php` - All REST API endpoints

## 🚀 Getting Started

### Prerequisites
- PHP 8.1 or higher
- Composer

### Installation

1. **Install dependencies:**
```bash
composer install
```

2. **Start the development server:**
```bash
php -S localhost:8000 -t public/
```

3. **Test the health endpoint:**
```bash
curl http://localhost:8000/api/health
```

## 📡 API Endpoints

### Document Management

#### **Upload a Document**
```bash
POST /api/documents
Content-Type: application/json

{
  "title": "Introduction to RAG",
  "content": "Retrieval-Augmented Generation (RAG) is a technique that combines retrieval of relevant documents with language model generation...",
  "metadata": {
    "author": "John Doe",
    "category": "AI"
  }
}
```

**Response:**
```json
{
  "success": true,
  "id": 1,
  "message": "Document added successfully"
}
```

#### **List All Documents**
```bash
GET /api/documents
```

**Response:**
```json
{
  "success": true,
  "documents": [
    {
      "id": 1,
      "title": "Introduction to RAG",
      "content": "Retrieval-Augmented Generation (RAG) is a technique...",
      "metadata": { "author": "John Doe" }
    }
  ],
  "count": 1
}
```

#### **Get Specific Document**
```bash
GET /api/documents/1
```

#### **Delete Document**
```bash
DELETE /api/documents/1
```

### Chat Interactions

#### **Send a Message (Streaming)**
```bash
POST /api/chat
Content-Type: application/json

{
  "session_id": "user-123",
  "message": "What is RAG?"
}
```

**Response (Server-Sent Events):**
```
data: {"chunk":"Based "}

data: {"chunk":"on "}

data: {"chunk":"the "}

data: {"chunk":"provided "}

data: {"chunk":"documents..."}

data: {"done":true}
```

#### **Get Conversation History**
```bash
GET /api/conversations/user-123
```

**Response:**
```json
{
  "success": true,
  "session_id": "user-123",
  "messages": [
    {
      "id": 1,
      "role": "user",
      "content": "What is RAG?",
      "sources": null,
      "timestamp": 1699564800
    },
    {
      "id": 2,
      "role": "assistant",
      "content": "RAG stands for...",
      "sources": [
        {
          "id": 1,
          "title": "Introduction to RAG",
          "score": 5.0
        }
      ],
      "timestamp": 1699564801
    }
  ],
  "count": 2
}
```

#### **Clear Conversation**
```bash
DELETE /api/conversations/user-123
```

## 💻 Usage Examples

### Using cURL

**1. Add some documents:**
```bash
# Add first document
curl -X POST http://localhost:8000/api/documents \
  -H "Content-Type: application/json" \
  -d '{
    "title": "What is RAG",
    "content": "RAG (Retrieval-Augmented Generation) enhances language models by retrieving relevant documents before generating responses."
  }'

# Add second document
curl -X POST http://localhost:8000/api/documents \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Benefits of RAG",
    "content": "RAG provides more accurate and factual responses by grounding generations in retrieved knowledge."
  }'
```

**2. Start a chat conversation:**
```bash
curl -X POST http://localhost:8000/api/chat \
  -H "Content-Type: application/json" \
  -d '{
    "session_id": "demo-session",
    "message": "Tell me about RAG and its benefits"
  }'
```

**3. View conversation history:**
```bash
curl http://localhost:8000/api/conversations/demo-session
```

### Using PHP

```php
<?php

// Initialize the API base URL
$baseUrl = 'http://localhost:8000/api';

// Function to make API calls
function apiRequest($method, $endpoint, $data = null) {
    global $baseUrl;
    $ch = curl_init($baseUrl . $endpoint);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Add a document
$result = apiRequest('POST', '/documents', [
    'title' => 'RAG Overview',
    'content' => 'Detailed explanation of RAG...'
]);

echo "Document ID: " . $result['id'] . "\n";

// Send a chat message
$chatResult = apiRequest('POST', '/chat', [
    'session_id' => 'php-session',
    'message' => 'Explain RAG in simple terms'
]);

// Get conversation history
$history = apiRequest('GET', '/conversations/php-session');
print_r($history);
```

## 🔧 Configuration

### Using OpenAI Instead of Mock

To use real OpenAI API instead of the mock client, modify `src/api/routes.php`:

```php
// Replace this line:
$llmClient = new MockLlmClient();

// With:
use App\Llm\OpenAiClient;
$llmClient = new OpenAiClient(
    apiKey: getenv('OPENAI_API_KEY') ?: 'your-api-key-here',
    model: 'gpt-4o-mini'
);
```

Then set your API key:
```bash
export OPENAI_API_KEY='sk-...'
php -S localhost:8000 -t public/
```

## 🧪 Testing

The mock LLM client simulates streaming responses with realistic delays. Test the system without external API calls:

```bash
# Test document upload
curl -X POST http://localhost:8000/api/documents \
  -H "Content-Type: application/json" \
  -d '{"title":"Test","content":"This is a test document with relevant information."}'

# Test chat with RAG
curl -X POST http://localhost:8000/api/chat \
  -H "Content-Type: application/json" \
  -d '{"session_id":"test-1","message":"What can you tell me about the test?"}'
```

## 🚀 Deployment

### AWS Lambda with Bref

1. **Install Bref:**
```bash
composer require bref/bref
```

2. **Create `serverless.yml`:**
```yaml
service: rag-chatbot

provider:
  name: aws
  region: us-east-1
  runtime: provided.al2

functions:
  api:
    handler: public/index.php
    timeout: 300
    layers:
      - ${bref:layer.php-81-fpm}
    events:
      - httpApi: '*'
    environment:
      OPENAI_API_KEY: ${env:OPENAI_API_KEY}
```

3. **Deploy:**
```bash
serverless deploy
```

### Traditional Server

For traditional PHP hosting, point your web server document root to `public/` directory.

**Apache `.htaccess` (already configured):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

## 🏛️ Design Principles

### Separation of Concerns
- **Domain Models**: Pure business logic (Documents, Messages, Conversations)
- **Repositories**: Data access abstraction
- **Services**: Business logic orchestration
- **API Layer**: HTTP concerns only

### Interface-Based Design
All storage and external services use interfaces, making it easy to:
- Replace in-memory storage with PostgreSQL or Redis
- Swap mock LLM with OpenAI, Anthropic, or local models
- Switch from keyword search to vector embeddings

### Dependency Injection
Services receive dependencies via constructor, making the code:
- Testable (easy to inject mocks)
- Flexible (easy to swap implementations)
- Clear (explicit dependencies)

## 🔄 Extending the System

### Add PostgreSQL with pgvector

```php
class PostgresVectorRepository implements VectorRepositoryInterface {
    public function search(string $query, int $limit = 3): array {
        // Use pgvector for semantic search
        $embedding = $this->getEmbedding($query);
        $results = $this->db->query(
            "SELECT * FROM documents ORDER BY embedding <-> ? LIMIT ?",
            [$embedding, $limit]
        );
        return $results;
    }
}
```

### Add Redis for Chat History

```php
class RedisChatRepository implements ChatRepositoryInterface {
    public function __construct(private Redis $redis) {}
    
    public function addMessage(string $sessionId, Message $message): void {
        $key = "conversation:{$sessionId}";
        $this->redis->rPush($key, json_encode($message));
        $this->redis->expire($key, 86400); // 24 hour TTL
    }
}
```

## 📝 File Structure

```
php-practice/
├── composer.json
├── public/
│   └── index.php              # Entry point
├── src/
│   ├── Retrieval/             # Document storage & search module
│   │   ├── Models.php
│   │   ├── VectorRepositoryInterface.php
│   │   └── InMemoryVectorRepository.php
│   ├── Chat/                  # Conversation & RAG module
│   │   ├── Models.php
│   │   ├── ChatRepositoryInterface.php
│   │   ├── InMemoryChatRepository.php
│   │   └── ChatService.php
│   ├── Llm/                   # LLM integration module
│   │   ├── LlmClientInterface.php
│   │   ├── MockLlmClient.php
│   │   └── OpenAiClient.php
│   └── api/
│       └── routes.php         # API endpoints
└── README.md
```

## 🤝 Contributing

This is a practice project demonstrating clean architecture principles. Feel free to:
- Add new repository implementations
- Integrate different LLM providers
- Improve the search algorithm
- Add authentication/authorization

## 📄 License

MIT License



