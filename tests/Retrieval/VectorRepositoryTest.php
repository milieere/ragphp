<?php

use PHPUnit\Framework\TestCase;
use App\Retrieval\{Document, InMemoryVectorRepository};

/**
 * Test VectorRepository CRUD and search operations
 */
class VectorRepositoryTest extends TestCase {
    
    private InMemoryVectorRepository $repo;
    
    protected function setUp(): void {
        $this->repo = new InMemoryVectorRepository();
    }
    
    public function test_adds_document_and_assigns_id(): void {
        $doc = new Document(null, 'Test', 'Content for testing purposes');
        
        $this->repo->add($doc);
        
        $this->assertNotNull($doc->id);
        $this->assertEquals(1, $doc->id);
    }
    
    public function test_retrieves_document_by_id(): void {
        $doc = new Document(null, 'Test', 'Content for testing');
        $this->repo->add($doc);
        
        $retrieved = $this->repo->get($doc->id);
        
        $this->assertNotNull($retrieved);
        $this->assertEquals('Test', $retrieved->title);
    }
    
    public function test_returns_null_for_nonexistent_id(): void {
        $result = $this->repo->get(999);
        
        $this->assertNull($result);
    }
    
    public function test_gets_all_documents(): void {
        $this->repo->add(new Document(null, 'Doc 1', 'Content one here'));
        $this->repo->add(new Document(null, 'Doc 2', 'Content two here'));
        
        $all = $this->repo->getAll();
        
        $this->assertCount(2, $all);
    }
    
    public function test_deletes_document(): void {
        $doc = new Document(null, 'Test', 'Content for testing');
        $this->repo->add($doc);
        
        $deleted = $this->repo->delete($doc->id);
        
        $this->assertTrue($deleted);
        $this->assertNull($this->repo->get($doc->id));
    }
    
    public function test_delete_returns_false_for_nonexistent(): void {
        $deleted = $this->repo->delete(999);
        
        $this->assertFalse($deleted);
    }
    
    public function test_searches_and_returns_relevant_documents(): void {
        $this->repo->add(new Document(null, 'PHP Tutorial', 'Learn PHP programming with examples'));
        $this->repo->add(new Document(null, 'Python Guide', 'Python programming for beginners'));
        $this->repo->add(new Document(null, 'PHP Best Practices', 'PHP coding standards and patterns'));
        
        $results = $this->repo->search('PHP programming', 2);
        
        $this->assertCount(2, $results);
        $this->assertStringContainsString('PHP', $results[0]->document->title);
        $this->assertGreaterThan(0, $results[0]->score);
    }
    
    public function test_search_respects_limit(): void {
        for ($i = 1; $i <= 5; $i++) {
            $this->repo->add(new Document(
                null, 
                "Document $i", 
                "Content with keyword test in document $i"
            ));
        }
        
        $results = $this->repo->search('test', 3);
        
        $this->assertCount(3, $results);
    }
    
    public function test_search_returns_empty_for_no_matches(): void {
        $this->repo->add(new Document(null, 'PHP', 'PHP content here'));
        
        $results = $this->repo->search('javascript', 5);
        
        $this->assertCount(0, $results);
    }
}

