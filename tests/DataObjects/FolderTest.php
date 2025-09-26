<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\Tests\DataObjects;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Plan2net\CmisBridge\DataObjects\Folder;
use Plan2net\CmisBridge\Session;

/**
 * Test for Folder class
 */
class FolderTest extends TestCase
{
    /**
     * @return Session&MockObject
     */
    private function createMockSession(): MockObject
    {
        return $this->createMock(Session::class);
    }

    private function createMockFolderProperties(): array
    {
        return [
            'cmis:objectId' => ['value' => 'test-folder-123'],
            'cmis:name' => ['value' => 'Test Folder'],
            'cmis:creationDate' => ['value' => 1669366179934],
            'cmis:lastModificationDate' => ['value' => 1669366199000],
            'cmis:createdBy' => ['value' => 'testuser'],
            'cmis:objectTypeId' => ['value' => 'cmis:folder'],
            'cmis:parentId' => ['value' => 'parent-folder-123']
        ];
    }

    public function testConstructorSetsSession(): void
    {
        $mockSession = $this->createMockSession();
        $folder = new Folder($mockSession);

        $this->assertInstanceOf(Folder::class, $folder);
    }

    public function testSetAndGetId(): void
    {
        $mockSession = $this->createMockSession();
        $folder = new Folder($mockSession);

        $folder->setId('folder-123');
        $this->assertEquals('folder-123', $folder->getId());
    }

    public function testSetAndGetName(): void
    {
        $mockSession = $this->createMockSession();
        $folder = new Folder($mockSession);

        $folder->setName('My Folder');
        $this->assertEquals('My Folder', $folder->getName());
    }

    public function testSetAndGetProperties(): void
    {
        $mockSession = $this->createMockSession();
        $folder = new Folder($mockSession);

        $properties = $this->createMockFolderProperties();
        $folder->setProperties($properties);

        $this->assertEquals('test-folder-123', $folder->getPropertyValue('cmis:objectId'));
        $this->assertEquals('Test Folder', $folder->getPropertyValue('cmis:name'));
        $this->assertEquals('testuser', $folder->getPropertyValue('cmis:createdBy'));
    }

    public function testGetPropertyValueReturnsNullForMissingProperty(): void
    {
        $mockSession = $this->createMockSession();
        $folder = new Folder($mockSession);

        $this->assertNull($folder->getPropertyValue('nonexistent:property'));
    }

    public function testGetCreationDateWithMillisecondTimestamp(): void
    {
        $mockSession = $this->createMockSession();
        $folder = new Folder($mockSession);

        $properties = $this->createMockFolderProperties();
        $folder->setProperties($properties);

        $creationDate = $folder->getCreationDate();
        $this->assertInstanceOf(\DateTime::class, $creationDate);

        // Convert milliseconds to seconds for comparison
        $expectedTimestamp = intval(1669366179934 / 1000);
        $this->assertEquals($expectedTimestamp, $creationDate->getTimestamp());
    }

    public function testGetLastModificationDateWithMillisecondTimestamp(): void
    {
        $mockSession = $this->createMockSession();
        $folder = new Folder($mockSession);

        $properties = $this->createMockFolderProperties();
        $folder->setProperties($properties);

        $modDate = $folder->getLastModificationDate();
        $this->assertInstanceOf(\DateTime::class, $modDate);

        $expectedTimestamp = intval(1669366199000 / 1000);
        $this->assertEquals($expectedTimestamp, $modDate->getTimestamp());
    }

    public function testGetCreationDateReturnsNullWhenMissing(): void
    {
        $mockSession = $this->createMockSession();
        $folder = new Folder($mockSession);

        // Set properties without creation date
        $folder->setProperties(['cmis:name' => ['value' => 'Test']]);

        $this->assertNull($folder->getCreationDate());
    }

    public function testGetCreatedBy(): void
    {
        $mockSession = $this->createMockSession();
        $folder = new Folder($mockSession);

        $properties = $this->createMockFolderProperties();
        $folder->setProperties($properties);

        $this->assertEquals('testuser', $folder->getCreatedBy());
    }

    public function testGetParentId(): void
    {
        $mockSession = $this->createMockSession();
        $folder = new Folder($mockSession);

        $properties = $this->createMockFolderProperties();
        $folder->setProperties($properties);

        $this->assertEquals('parent-folder-123', $folder->getParentId());
    }

    public function testGetChildrenCallsSessionCacheFirst(): void
    {
        $mockSession = $this->createMockSession();

        // Mock getCachedChildren to return cached result
        $mockSession->expects($this->once())
                   ->method('getCachedChildren')
                   ->with('folder-123')
                   ->willReturn(['cached-child']);

        $folder = new Folder($mockSession);
        $folder->setId('folder-123');

        $children = $folder->getChildren();
        $this->assertEquals(['cached-child'], $children);
    }

    public function testEmptyPropertiesHandling(): void
    {
        $mockSession = $this->createMockSession();
        $folder = new Folder($mockSession);

        // Test with empty properties array
        $folder->setProperties([]);

        $this->assertNull($folder->getPropertyValue('cmis:name'));
        $this->assertNull($folder->getCreationDate());
        $this->assertNull($folder->getCreatedBy());
        $this->assertNull($folder->getParentId());
    }
}
