<?php
declare(strict_types=1);

namespace App\Shared\Clients\Llm;

use App\Shared\Clients\Llm\LlmClientInterface;
use Generator;
use InvalidArgumentException;

class MockLlmClient implements LlmClientInterface {
    public function __construct(private string $apiKey) {}

    public function streamCompletion(string $prompt): Generator {
        $response = "This is a mock response to your prompt: $prompt from an LLM!";
        foreach (explode(' ', $response) as $token) {
            yield $token;
        }
    }
}