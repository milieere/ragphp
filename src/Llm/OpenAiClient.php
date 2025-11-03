<?php

namespace App\Llm;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * OpenAI LLM client with streaming support
 * Uses the Chat Completions API
 */
class OpenAiClient implements LlmClientInterface {
    private const API_URL = 'https://api.openai.com/v1/chat/completions';
    
    private Client $httpClient;
    
    public function __construct(
        private string $apiKey,
        private string $model = 'gpt-4o-mini'
    ) {
        $this->httpClient = new Client([
            'timeout' => 60,
            'stream' => true,
        ]);
    }
    
    public function streamCompletion(string $prompt): \Generator {
        try {
            $response = $this->httpClient->post(self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'stream' => true,
                    'temperature' => 0.7,
                ],
            ]);
            
            $body = $response->getBody();
            
            while (!$body->eof()) {
                $line = $this->readLine($body);
                
                if (empty($line) || $line === 'data: [DONE]') {
                    continue;
                }
                
                if (str_starts_with($line, 'data: ')) {
                    $json = substr($line, 6);
                    $data = json_decode($json, true);
                    
                    if (isset($data['choices'][0]['delta']['content'])) {
                        yield $data['choices'][0]['delta']['content'];
                    }
                }
            }
        } catch (GuzzleException $e) {
            throw new \RuntimeException('OpenAI API error: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Read a line from the stream
     */
    private function readLine($stream): string {
        $line = '';
        while (!$stream->eof()) {
            $char = $stream->read(1);
            if ($char === "\n") {
                break;
            }
            $line .= $char;
        }
        return trim($line);
    }
}



