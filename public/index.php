<?php

/**
 * RAG Chatbot Backend - Entry Point
 * 
 * This file serves as the main entry point for the RAG chatbot API.
 * It loads and runs the Slim application with all routes.
 */

// Load and run the application
$app = require __DIR__ . '/../src/api/routes.php';
$app->run();
