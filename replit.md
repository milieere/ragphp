# RAG Chatbot Backend

## Project Overview
This is a clean, modular RAG (Retrieval-Augmented Generation) chatbot backend built with PHP, following best practices for OOP architecture and separation of concerns.

## Recent Setup (November 3, 2025)
- Installed PHP 8.4 for Replit environment
- Configured server to run on port 5000 (required for Replit)
- Installed all Composer dependencies
- Set up workflow for the API server

## Project Architecture
The application is organized into three main modules:
1. **Retrieval Module** (`src/Retrieval/`) - Document storage and semantic search
2. **Chat Module** (`src/Chat/`) - Conversation management and RAG orchestration
3. **LLM Module** (`src/Llm/`) - Language model integration (Mock and OpenAI)
4. **API Layer** (`src/api/`) - HTTP endpoints using Slim Framework

## Key Technologies
- PHP 8.4
- Slim Framework (REST API)
- Guzzle HTTP (OpenAI client)
- In-memory storage (can be swapped for PostgreSQL/Redis)

## Running the Application
The server runs automatically via the workflow on port 5000. The API is available at:
- Base URL: `http://localhost:5000/api`
- Health check: `GET /api/health`

## API Endpoints

### Document Management
- `POST /api/documents` - Upload a document to the knowledge base
- `GET /api/documents` - List all documents
- `GET /api/documents/{id}` - Get a specific document
- `DELETE /api/documents/{id}` - Delete a document

### Chat Interactions
- `POST /api/chat` - Send a message (streaming response)
- `GET /api/conversations/{session_id}` - Get conversation history
- `DELETE /api/conversations/{session_id}` - Clear conversation

### Health
- `GET /api/health` - Health check endpoint

## Using with OpenAI
By default, the app uses a MockLlmClient for testing. To use real OpenAI:

1. Set your OpenAI API key as a secret named `OPENAI_API_KEY`
2. Edit `src/api/routes.php` line 27:
   ```php
   // Replace:
   $llmClient = new MockLlmClient();
   
   // With:
   use App\Llm\OpenAiClient;
   $llmClient = new OpenAiClient(
       apiKey: getenv('OPENAI_API_KEY'),
       model: 'gpt-4o-mini'
   );
   ```
3. Restart the workflow

## User Preferences
None set yet.

## Development Notes
- The server uses PHP's built-in development server
- All dependencies are managed via Composer
- The project follows PSR-4 autoloading standards
- Clean architecture with dependency injection throughout
