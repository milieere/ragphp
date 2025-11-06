<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Creates and configures the application logger
 * 
 * @return Logger
 */
return function(): Logger {
    $logger = new Logger('api');

    // Console handler - outputs to stdout (you'll see this in terminal)
    $streamHandler = new StreamHandler('php://stdout', Logger::DEBUG);

    // Custom format for better readability
    $formatter = new LineFormatter(
        "[%datetime%] %channel%.%level_name%: %message% %context%\n",
        "Y-m-d H:i:s",
        true,
        true
    );
    $streamHandler->setFormatter($formatter);

    $logger->pushHandler($streamHandler);

    return $logger;
};

