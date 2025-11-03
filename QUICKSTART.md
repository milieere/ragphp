# Quick Start Guide

## Step 1: Install Dependencies

```bash
composer install
```

## Step 2: Test the Core Functionality (No Server Needed)

Run the example script to see RAG in action:

```bash
php example_rag_usage.php
```

This will:
- Add documents to the knowledge base
- Test search functionality
- Demonstrate RAG chat with context retrieval
- Show conversation history

## Step 3: Start the API Server

```bash
php -S localhost:8000 -t public/
```

## Step 4: Test the API

### Add a document:
```bash
curl -X POST http://localhost:8000/api/documents \
  -H "Content-Type: application/json" \
  -d '{
    "title": "PHP Best Practices",
    "content": "Always use type hints, follow PSR standards, and write clean, testable code."
  }'
```

### Send a chat message:
```bash
curl -X POST http://localhost:8000/api/chat \
  -H "Content-Type: application/json" \
  -d '{
    "session_id": "test-123",
    "message": "What are PHP best practices?"
  }'
```

### View conversation history:
```bash
curl http://localhost:8000/api/conversations/test-123
```

## Step 5: Try the Full Workflow

```bash
# 1. Add multiple documents
curl -X POST http://localhost:8000/api/documents \
  -H "Content-Type: application/json" \
  -d '{"title": "Document 1", "content": "Content about topic A..."}'

curl -X POST http://localhost:8000/api/documents \
  -H "Content-Type: application/json" \
  -d '{"title": "Document 2", "content": "More information about topic A..."}'

# 2. List all documents
curl http://localhost:8000/api/documents

# 3. Start chatting (will retrieve relevant docs)
curl -X POST http://localhost:8000/api/chat \
  -H "Content-Type: application/json" \
  -d '{"session_id": "user-1", "message": "Tell me about topic A"}'

# 4. Continue the conversation
curl -X POST http://localhost:8000/api/chat \
  -H "Content-Type: application/json" \
  -d '{"session_id": "user-1", "message": "Can you explain more?"}'

# 5. Check history
curl http://localhost:8000/api/conversations/user-1
```

## Using with OpenAI

1. Get your API key from https://platform.openai.com/api-keys

2. Edit `src/api/routes.php` and replace:
```php
$llmClient = new MockLlmClient();
```

with:
```php
use App\Llm\OpenAiClient;
$llmClient = new OpenAiClient('your-api-key-here', 'gpt-4o-mini');
```

3. Restart the server and try again!

## Project Structure

```
src/
├── Retrieval/      # Document storage & search
├── Chat/           # Conversations & RAG orchestration
├── Llm/            # LLM client implementations
└── api/            # HTTP endpoints
```

Each module is self-contained with:
- Models (domain entities)
- Interface (abstraction)
- Implementation (concrete class)

## Next Steps

- Check the full [README.md](README.md) for detailed documentation
- Explore the module-based architecture
- Try swapping implementations (e.g., PostgreSQL instead of in-memory)
- Deploy to AWS Lambda with streaming support

Happy coding! 🚀



