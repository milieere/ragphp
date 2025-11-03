<?php

namespace App\Retrieval;

/**
 * Represents a document in the knowledge base
 */
class Document {
    public function __construct(
        public ?int $id,
        public string $title,
        public string $content,
        public array $metadata = []
    ) {
        if (empty($title)) {
            throw new \InvalidArgumentException('Title is required');
        }
        if (empty($content)) {
            throw new \InvalidArgumentException('Content is required');
        }
        if (strlen($content) < 10) {
            throw new \InvalidArgumentException('Content too short (minimum 10 characters)');
        }
    }
}

/**
 * Represents a search result with relevance score
 */
class SearchResult {
    public function __construct(
        public Document $document,
        public float $score
    ) {
        if ($score < 0) {
            throw new \InvalidArgumentException('Score must be non-negative');
        }
    }
}

