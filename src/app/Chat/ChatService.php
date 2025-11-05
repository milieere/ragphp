<?php

namespace App\Chat;
use App\Chat\ChatMessage;

class ChatService{
    public function __construct(
        private ChatRepository $chatRepository,)
}