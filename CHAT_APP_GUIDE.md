# Chat Application - Quick Start Guide

## What's Been Built

A complete chat application with:
- ✅ Clean architecture (Service → Repository → Database)
- ✅ Dependency Injection container
- ✅ SQLite database with automatic table creation
- ✅ RESTful API with streaming support
- ✅ Modern HTML/JavaScript interface
- ✅ Multiple chat sessions support

## How to Run

### 1. Start the PHP development server

```bash
cd /Users/simona/code/php-practice
php -S localhost:8000 -t public
```

### 2. Open the chat interface

Open your browser and go to:
```
http://localhost:8000/chat.html
```

## How to Use

### Create a New Chat
1. Click the "+ New Chat" button
2. Enter a name for your chat
3. The chat will be created and selected automatically

### Send Messages
1. Select a chat from the sidebar (or create a new one)
2. Type your message in the input box
3. Click "Send" or press Enter
4. Watch the response stream in real-time!

### Switch Between Chats
- Click any chat in the sidebar to switch to it
- All messages are saved and persist across page refreshes
- The URL updates so you can bookmark specific chats

## API Endpoints

All endpoints are available at `http://localhost:8000/api/`

### Chats
- `GET /api/chats` - List all chats
- `GET /api/chats/{chatId}` - Get single chat
- `POST /api/chats` - Create new chat
  ```json
  { "name": "My Chat", "description": "Optional description" }
  ```
- `DELETE /api/chats/{chatId}` - Delete a chat

### Messages
- `GET /api/chats/{chatId}/messages` - Get chat history
- `POST /api/chats/{chatId}/messages` - Send message (streaming)
  ```json
  { "message": "Hello!" }
  ```

## Architecture

```
public/chat.html           → HTML interface
    ↓
src/api/routes.php        → API endpoints
    ↓
src/Chat/ChatService.php  → Business logic
    ↓
src/Chat/Repository/SQLiteChatRepository.php → Data access
    ↓
data/chat.db              → SQLite database
```

## Dependency Injection

Everything is wired up in `src/api/container.php`:
- Database (PDO with SQLite)
- Repository (SQLiteChatRepository)
- LLM Client (MockLlmClient - simulates AI responses)
- ChatService (orchestrates everything)
- Logger (PSR-3 compliant)

## Database

The SQLite database is created automatically at:
```
data/chat.db
```

Tables:
- `chats` - Chat sessions (id, name, description, timestamps)
- `chat_messages` - Messages (id, chat_id, role, content, timestamp)

## Testing the API

### Using curl:

```bash
# Create a chat
curl -X POST http://localhost:8000/api/chats \
  -H "Content-Type: application/json" \
  -d '{"name": "Test Chat", "description": "Testing"}'

# List chats
curl http://localhost:8000/api/chats

# Send a message (will stream)
curl -X POST http://localhost:8000/api/chats/{chatId}/messages \
  -H "Content-Type: application/json" \
  -d '{"message": "Hello!"}'
```

## What's Next?

Current implementation uses `MockLlmClient` which returns fake responses. To integrate a real LLM:

1. Implement a real LLM client (e.g., OpenAI):
   ```php
   class OpenAiLlmClient implements LlmClientInterface {
       public function streamCompletion(string $prompt): Generator {
           // Call OpenAI API and yield chunks
       }
   }
   ```

2. Update `container.php`:
   ```php
   LlmClientInterface::class => function() {
       return new OpenAiLlmClient($_ENV['OPENAI_API_KEY']);
   }
   ```

## Troubleshooting

### Database errors
- Make sure the `data/` directory exists and is writable
- Delete `data/chat.db` to reset the database

### Nothing shows in the UI
- Check browser console for JavaScript errors
- Verify the PHP server is running
- Test the API directly with curl

### Messages not saving
- Check that the repository is properly wired in the container
- Look at PHP error logs

## Project Structure

```
php-practice/
├── data/              # SQLite database (created automatically)
├── public/            # Web root
│   ├── chat.html     # Main interface
│   └── index.php     # API entry point
├── src/
│   ├── api/
│   │   ├── container.php  # DI configuration
│   │   └── routes.php     # API routes
│   ├── Chat/
│   │   ├── ChatService.php          # Business logic
│   │   ├── Models/
│   │   │   ├── Chat.php
│   │   │   ├── ChatMessage.php
│   │   │   └── ChatRole.php
│   │   └── Repository/
│   │       ├── ChatRepositoryInterface.php
│   │       ├── SQLiteChatRepository.php
│   │       └── Mappers/
│   │           ├── ChatMapper.php
│   │           └── ChatMessageMapper.php
│   └── Shared/
│       ├── Clients/Llm/
│       │   ├── LlmClientInterface.php
│       │   └── MockLlmClient.php
│       └── Database/Tables/
│           ├── Chats.sql
│           └── ChatMessages.sql
└── tests/            # PHPUnit tests
```

Enjoy your chat application! 🎉

