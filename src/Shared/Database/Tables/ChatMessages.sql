CREATE TABLE IF NOT EXISTS chat_messages (
  id TEXT PRIMARY KEY,
  chat_id TEXT NOT NULL,
  role TEXT NOT NULL,
  content TEXT NOT NULL,
  timestamp DATETIME
);

CREATE INDEX IF NOT EXISTS idx_chat_id ON chat_messages(chat_id);
CREATE INDEX IF NOT EXISTS idx_chat_id_created ON chat_messages(chat_id, timestamp);
