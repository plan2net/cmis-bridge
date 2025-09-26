<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\Tests\DataObjects;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Plan2net\CmisBridge\DataObjects\Document;
use Plan2net\CmisBridge\Session;

/**
 * Test for Document class
 */
class DocumentTest extends TestCase
{
    /**
     * @return Session&MockObject
     */
    private function createMockSession()
    {
        return $this->createMock(Session::class);
    }

    private function createMockProperties(): array
    {
        return [
            'cmis:objectId' => ['value' => 'test-doc-123'],
            'cmis:name' => ['value' => 'Test Document.pdf'],
            'cmis:creationDate' => ['value' => 1669366179934], // Unix timestamp in milliseconds
            'cmis:lastModificationDate' => ['value' => 1669366199000],
            'cmis:createdBy' => ['value' => 'testuser'],
            'cmis:contentStreamLength' => ['value' => 2048],
            'cmis:contentStreamMimeType' => ['value' => 'application/pdf'],
            'cmis:versionSeriesId' => ['value' => 'version-123']
        ];
    }

    public function testConstructorSetsSession(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);

        // Document should be created without error
        $this->assertInstanceOf(Document::class, $document);
    }

    public function testSetAndGetId(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);

        $id = 'test-document-456';
        $document->setId($id);

        $this->assertSame($id, $document->getId());
    }

    public function testSetAndGetName(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);

        $name = 'Important Document.docx';
        $document->setName($name);

        $this->assertSame($name, $document->getName());
    }

    public function testSetAndGetProperties(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);

        $properties = $this->createMockProperties();
        $document->setProperties($properties);

        $this->assertSame('test-doc-123', $document->getPropertyValue('cmis:objectId'));
        $this->assertSame('Test Document.pdf', $document->getPropertyValue('cmis:name'));
    }

    public function testGetPropertyValueReturnsNull(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);

        // Property that doesn't exist should return null
        $this->assertNull($document->getPropertyValue('nonexistent:property'));
    }

    public function testGetCreationDate(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);
        $document->setProperties($this->createMockProperties());

        $creationDate = $document->getCreationDate();

        $this->assertInstanceOf(\DateTime::class, $creationDate);
        // Verify it's the correct date (timestamp 1669366179934 / 1000)
        $this->assertEquals(1669366179, $creationDate->getTimestamp());
    }

    public function testGetLastModificationDate(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);
        $document->setProperties($this->createMockProperties());

        $modificationDate = $document->getLastModificationDate();

        $this->assertInstanceOf(\DateTime::class, $modificationDate);
        // Verify it's the correct date (timestamp 1669366199000 / 1000)
        $this->assertEquals(1669366199, $modificationDate->getTimestamp());
    }

    public function testGetCreatedBy(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);
        $document->setProperties($this->createMockProperties());

        $this->assertSame('testuser', $document->getCreatedBy());
    }

    public function testGetContentStreamLength(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);
        $document->setProperties($this->createMockProperties());

        $this->assertSame(2048, $document->getContentStreamLength());
    }

    public function testGetContentStreamMimeType(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);
        $document->setProperties($this->createMockProperties());

        $this->assertSame('application/pdf', $document->getContentStreamMimeType());
    }

    public function testGetVersionSeriesId(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);
        $document->setProperties($this->createMockProperties());

        $this->assertSame('version-123', $document->getVersionSeriesId());
    }

    public function testGetCreationDateWithStringDate(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);

        // Test with string date format
        $properties = $this->createMockProperties();
        $properties['cmis:creationDate']['value'] = '2022-11-25T12:30:00Z';
        $document->setProperties($properties);

        $creationDate = $document->getCreationDate();

        $this->assertInstanceOf(\DateTime::class, $creationDate);
    }

    public function testGetDateReturnsNullForMissingProperty(): void
    {
        $mockSession = $this->createMockSession();
        $document = new Document($mockSession);

        // No properties set, should return null
        $this->assertNull($document->getCreationDate());
        $this->assertNull($document->getLastModificationDate());
    }
}
