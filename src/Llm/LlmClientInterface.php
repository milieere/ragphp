<?php

namespace App\Llm;

/**
 * Interface for LLM (Large Language Model) clients
 * Can be implemented for OpenAI, Anthropic, local models, etc.
 */
interface LlmClientInterface {
    /**
     * Stream completion response from the LLM
     * 
     * @param string $prompt The input prompt
     * @return \Generator Yields chunks of text as they arrive
     */
    public function streamCompletion(string $prompt): \Generator;
}
