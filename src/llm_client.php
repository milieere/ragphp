<?php

class LlmClient {
    private string $apiKey;
    private string $baseUrl;

    public function __construct(string $apiKey, string $baseUrl) {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }
    
    public function generateText(string $prompt): string {
        $url = $this->baseUrl . '/generate';
        $response = Http::post($url, [
            'prompt' => $prompt,
            'apiKey' => $this->apiKey,
        ]);
        return $response->body();
    }
}