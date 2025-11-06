<?php
declare(strict_types=1);

namespace App\Shared\Clients\Llm;

use Generator;

interface LlmClientInterface {
    public function streamCompletion(string $prompt): Generator;
}