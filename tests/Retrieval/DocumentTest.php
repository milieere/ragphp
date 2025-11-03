<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/Retrieval/Models.php';

use App\Retrieval\Document;

/**
 * Test Document model validation and behavior
 */
class DocumentTest extends TestCase {
    
    public function test_creates_document_with_valid_data(): void {
        $doc = new Document(
            id: 1,
            title: 'Test Document',
            content: 'This is test content that is long enough.'
        );
        
        $this->assertEquals(1, $doc->id);
        $this->assertEquals('Test Document', $doc->title);
        $this->assertStringContainsString('test content', $doc->content);
    }
    
    public function test_throws_exception_when_title_is_empty(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Title is required');
        
        new Document(
            id: 1,
            title: '',
            content: 'Content here'
        );
    }
    
    public function test_throws_exception_when_content_is_empty(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Content is required');
        
        new Document(
            id: 1,
            title: 'Title',
            content: ''
        );
    }
    
    public function test_throws_exception_when_content_too_short(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Content too short');
        
        new Document(
            id: 1,
            title: 'Title',
            content: 'Short'
        );
    }
    
    public function test_accepts_metadata(): void {
        $doc = new Document(
            id: 1,
            title: 'Test',
            content: 'Content with enough length',
            metadata: ['author' => 'John', 'category' => 'Tech']
        );
        
        $this->assertEquals('John', $doc->metadata['author']);
        $this->assertEquals('Tech', $doc->metadata['category']);
    }
}

