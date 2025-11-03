<?php

namespace App\Llm;

/**
 * Mock LLM client for testing and development
 * Simulates streaming responses with realistic delays
 */
class MockLlmClient implements LlmClientInterface {
    public function streamCompletion(string $prompt): \Generator {
        $response = $this->generateMockResponse($prompt);
        $words = explode(' ', $response);
        
        // Simulate streaming by yielding words with delays
        foreach ($words as $word) {
            usleep(50000); // 50ms delay between words
            yield $word . ' ';
        }
    }
    
    /**
     * Generate a mock response based on the prompt
     */
    private function generateMockResponse(string $prompt): string {
        // Extract user question from prompt
        if (preg_match('/User: (.+?)(?:\n|$)/s', $prompt, $matches)) {
            $question = trim($matches[1]);
        } else {
            $question = 'your question';
        }
        
        $responses = [
            "Based on the provided documents, I can help you with {$question}. The information suggests that this is an important topic with multiple facets to consider.",
            "Thank you for asking about {$question}. According to the knowledge base, there are several key points to understand about this subject.",
            "I'd be happy to help with {$question}. From the documents provided, we can see that this involves several important considerations.",
        ];
        
        return $responses[array_rand($responses)];
    }
}



