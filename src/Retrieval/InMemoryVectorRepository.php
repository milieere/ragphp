<?php

namespace App\Retrieval;

/**
 * Simple in-memory vector repository using keyword-based search
 * In production, replace with pgvector, Pinecone, or similar
 */
class InMemoryVectorRepository implements VectorRepositoryInterface {
    private array $documents = [];
    private int $nextId = 1;
    
    public function add(Document $document): void {
        if (!$document->id) {
            $document->id = $this->nextId++;
        }
        $this->documents[$document->id] = $document;
    }
    
    public function get(int $id): ?Document {
        return $this->documents[$id] ?? null;
    }
    
    public function getAll(): array {
        return array_values($this->documents);
    }
    
    public function delete(int $id): bool {
        if (isset($this->documents[$id])) {
            unset($this->documents[$id]);
            return true;
        }
        return false;
    }
    
    public function search(string $query, int $limit = 3): array {
        $results = [];
        $queryWords = $this->tokenize($query);
        
        if (empty($queryWords)) {
            return [];
        }
        
        foreach ($this->documents as $doc) {
            $score = $this->calculateScore($doc, $queryWords);
            if ($score > 0) {
                $results[] = new SearchResult($doc, $score);
            }
        }
        
        // Sort by score descending
        usort($results, fn($a, $b) => $b->score <=> $a->score);
        
        return array_slice($results, 0, $limit);
    }
    
    /**
     * Tokenize text into words
     */
    private function tokenize(string $text): array {
        $words = preg_split('/\s+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        return array_filter($words, fn($word) => strlen($word) > 2); // Filter short words
    }
    
    /**
     * Calculate relevance score using simple keyword matching
     * In production, this would use vector similarity (cosine, dot product, etc.)
     */
    private function calculateScore(Document $doc, array $queryWords): float {
        $content = strtolower($doc->title . ' ' . $doc->content);
        $score = 0.0;
        
        foreach ($queryWords as $word) {
            // Count occurrences, with title matches weighted higher
            $titleMatches = substr_count(strtolower($doc->title), $word);
            $contentMatches = substr_count(strtolower($doc->content), $word);
            
            $score += ($titleMatches * 2.0) + ($contentMatches * 1.0);
        }
        
        return $score;
    }
}


