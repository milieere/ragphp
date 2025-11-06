<?php

/**
 * Router script for PHP built-in server
 * This allows serving static files alongside the Slim application
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Get the requested URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // Let PHP's built-in server handle static files
}

// For the root path, serve index.html if it exists
if ($uri === '/' && file_exists(__DIR__ . '/index.html')) {
    readfile(__DIR__ . '/index.html');
    return true;
}

// Otherwise, route through the Slim application
require __DIR__ . '/index.php';
