<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Stream;


$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// Add JSON parsing middleware
$app->addBodyParsingMiddleware();

$logger = require __DIR__ . '/../Shared/Logger.php';

// ====================
// Document Endpoints
// ====================

$app->get('/api', function (Request $request, Response $response, LoggerInterface $logger) {
  return $response->write('Welcome to the RAG Chatbot API!');
}

return $app;
