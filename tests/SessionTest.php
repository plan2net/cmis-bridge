<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\Tests;

use CMIS\Session\Session as OptgovSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Plan2net\CmisBridge\Data\ObjectId;
use Plan2net\CmisBridge\Session;

/**
 * Test for Session class
 */
class SessionTest extends TestCase
{
    /**
     * @return OptgovSession&MockObject
     */
    private function createMockOptgovSession()
    {
        return $this->createMock(OptgovSession::class);
    }

    public function testConstructorSetsProperties(): void
    {
        $optgovSession = $this->createMockOptgovSession();
        $parameters = [
            'dkd.phpcmis.binding.browser.url' => 'https://test.example.com',
            'dkd.phpcmis.session.repository.id' => '-default-'
        ];

        $session = new Session($optgovSession, $parameters);

        $this->assertInstanceOf(Session::class, $session);
    }

    public function testCreateObjectId(): void
    {
        $optgovSession = $this->createMockOptgovSession();
        $parameters = [];
        $session = new Session($optgovSession, $parameters);

        $id = 'test-object-123';
        $objectId = $session->createObjectId($id);

        $this->assertInstanceOf(ObjectId::class, $objectId);
        $this->assertSame($id, $objectId->getId());
    }

    public function testGetOptgovSession(): void
    {
        $optgovSession = $this->createMockOptgovSession();
        $parameters = [];
        $session = new Session($optgovSession, $parameters);

        $this->assertSame($optgovSession, $session->getOptgovSession());
    }

    public function testCacheOperations(): void
    {
        $optgovSession = $this->createMockOptgovSession();
        $parameters = [];
        $session = new Session($optgovSession, $parameters);

        // Test cache children operations
        $folderId = 'test-folder-123';
        $children = ['child1', 'child2'];

        // Initially should return null (no cache)
        $this->assertNull($session->getCachedChildren($folderId));

        // Set cache
        $session->setCachedChildren($folderId, $children);

        // Now should return cached value
        $this->assertSame($children, $session->getCachedChildren($folderId));
    }

    public function testCacheParentsOperations(): void
    {
        $optgovSession = $this->createMockOptgovSession();
        $parameters = [];
        $session = new Session($optgovSession, $parameters);

        $objectId = 'test-object-456';
        $parents = ['parent1', 'parent2'];

        // Initially should return null (no cache)
        $this->assertNull($session->getCachedParents($objectId));

        // Set cache
        $session->setCachedParents($objectId, $parents);

        // Now should return cached value
        $this->assertSame($parents, $session->getCachedParents($objectId));
    }

    public function testCacheContentStreamMetadata(): void
    {
        $optgovSession = $this->createMockOptgovSession();
        $parameters = [];
        $session = new Session($optgovSession, $parameters);

        $objectId = 'test-doc-789';
        $metadata = ['length' => 1024, 'mimeType' => 'application/pdf'];

        // Initially should return null (no cache)
        $this->assertNull($session->getCachedContentStreamMetadata($objectId));

        // Set cache
        $session->setCachedContentStreamMetadata($objectId, $metadata);

        // Now should return cached value
        $this->assertSame($metadata, $session->getCachedContentStreamMetadata($objectId));
    }

    public function testRemoveObjectFromCacheWithString(): void
    {
        $optgovSession = $this->createMockOptgovSession();
        $parameters = [];
        $session = new Session($optgovSession, $parameters);

        $objectId = 'test-remove-123';
        $children = ['child1', 'child2'];

        // Set some cache data
        $session->setCachedChildren($objectId, $children);
        $this->assertSame($children, $session->getCachedChildren($objectId));

        // Remove from cache
        $session->removeObjectFromCache($objectId);

        // Should now return null
        $this->assertNull($session->getCachedChildren($objectId));
    }

    public function testRemoveObjectFromCacheWithObjectId(): void
    {
        $optgovSession = $this->createMockOptgovSession();
        $parameters = [];
        $session = new Session($optgovSession, $parameters);

        $id = 'test-remove-456';
        $objectId = $session->createObjectId($id);
        $parents = ['parent1'];

        // Set some cache data
        $session->setCachedParents($id, $parents);
        $this->assertSame($parents, $session->getCachedParents($id));

        // Remove from cache using ObjectId
        $session->removeObjectFromCache($objectId);

        // Should now return null
        $this->assertNull($session->getCachedParents($id));
    }

    public function testClearCache(): void
    {
        $optgovSession = $this->createMockOptgovSession();
        $parameters = [];
        $session = new Session($optgovSession, $parameters);

        // Set multiple cache entries
        $session->setCachedChildren('folder1', ['child1']);
        $session->setCachedParents('object1', ['parent1']);
        $session->setCachedContentStreamMetadata('doc1', ['size' => 100]);

        // Verify cache entries exist
        $this->assertNotNull($session->getCachedChildren('folder1'));
        $this->assertNotNull($session->getCachedParents('object1'));
        $this->assertNotNull($session->getCachedContentStreamMetadata('doc1'));

        // Clear all cache
        $session->clearCache();

        // All should now return null
        $this->assertNull($session->getCachedChildren('folder1'));
        $this->assertNull($session->getCachedParents('object1'));
        $this->assertNull($session->getCachedContentStreamMetadata('doc1'));
    }

    public function testGetRepositoryInfo(): void
    {
        $optgovSession = $this->createMockOptgovSession();
        $optgovSession->expects($this->once())
                     ->method('getRepositoryId')
                     ->willReturn('test-repo-id');

        $parameters = [];
        $session = new Session($optgovSession, $parameters);

        $repoInfo = $session->getRepositoryInfo();

        $this->assertSame('test-repo-id', $repoInfo['id']);
        $this->assertSame('1.1', $repoInfo['cmisVersionSupported']);
    }
}
