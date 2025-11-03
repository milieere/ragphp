<?php

namespace App\Retrieval;

/**
 * Interface for document storage and retrieval
 * Can be implemented with in-memory, PostgreSQL+pgvector, Pinecone, etc.
 */
interface VectorRepositoryInterface {
    /**
     * Add a document to the repository
     */
    public function add(Document $document): void;
    
    /**
     * Get a document by ID
     */
    public function get(int $id): ?Document;
    
    /**
     * Get all documents
     * @return Document[]
     */
    public function getAll(): array;
    
    /**
     * Delete a document by ID
     */
    public function delete(int $id): bool;
    
    /**
     * Search for documents relevant to the query
     * @return SearchResult[]
     */
    public function search(string $query, int $limit = 3): array;
}

