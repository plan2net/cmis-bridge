<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge;

use CMIS\Http\Request;
use CMIS\Session\Session as OptgovSession;
use GuzzleHttp\Exception\GuzzleException;
use Plan2net\CmisBridge\Data\ObjectId;
use Plan2net\CmisBridge\DataObjects\Document;
use Plan2net\CmisBridge\DataObjects\Folder;

/**
 * Bridge class that provides dkd/php-cmis Session interface using optigov/php-cmis-client
 */
class Session
{
    private OptgovSession $optgovSession;
    /**
     * @var array<string, mixed>
     */
    private array $parameters;

    // Enhanced caching system
    /**
     * @var array<string, Document|Folder>
     */
    private array $objectCache = [];           // Cache for CMIS objects by ID
    /**
     * @var array<string, array<int, Document|Folder>>
     */
    private array $childrenCache = [];         // Cache for folder children
    /**
     * @var array<string, array<int, Folder>>
     */
    private array $parentsCache = [];          // Cache for object parents
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $propertiesCache = [];       // Cache for object properties
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $contentStreamCache = [];    // Cache for content streams (metadata only)

    private ?Folder $rootFolder = null;

    public function __construct(OptgovSession $optgovSession, array $parameters)
    {
        $this->optgovSession = $optgovSession;
        $this->parameters = $parameters;
    }

    /**
     * Get the root folder of the repository
     *
     * @throws GuzzleException
     */
    public function getRootFolder(): Folder
    {
        if (null === $this->rootFolder) {
            // Use correct CMIS Browser binding endpoint for root folder
            $browserUrl = $this->optgovSession->getUrl();
            $rootUrl = $browserUrl . '/root';

            $request = new Request($rootUrl);
            $request->addUrlParameter('cmisselector', 'object');

            $response = $this->optgovSession->getHttpClient()->get($request);
            $data = json_decode((string) $response->getBody(), true);

            $this->rootFolder = $this->createFolderFromData($data);
        }

        return $this->rootFolder;
    }

    /**
     * Get a CMIS object by its ObjectId with caching
     *
     * @throws GuzzleException
     *
     * @return Document|Folder|null
     */
    public function getObject(ObjectId $objectId)
    {
        $id = $objectId->getId();

        // Check cache first
        if (isset($this->objectCache[$id])) {
            return $this->objectCache[$id];
        }

        try {
            // Use correct CMIS Browser binding endpoint for object
            $browserUrl = $this->optgovSession->getUrl();
            $rootUrl = $browserUrl . '/root';

            $request = new Request($rootUrl);
            $request->addUrlParameter('objectId', $id)
                   ->addUrlParameter('cmisselector', 'object')
                   ->addUrlParameter('succinct', 'false'); // Match dkd default

            $response = $this->optgovSession->getHttpClient()->get($request);
            $responseBody = (string) $response->getBody();
            $data = json_decode($responseBody, true);

            if (!isset($data['properties'])) {
                return null;
            }

            $properties = $data['properties'];
            $objectType = $properties['cmis:objectTypeId']['value'] ?? '';

            // Create appropriate object type based on CMIS type
            if (false !== strpos($objectType, 'cmis:folder')) {
                $object = new Folder($this);
            } else {
                $object = new Document($this);
            }

            $object->setId($properties['cmis:objectId']['value']);
            $object->setName($properties['cmis:name']['value'] ?? '');
            $object->setProperties($properties);

            // Cache the object
            $this->objectCache[$id] = $object;

            // Also cache the properties separately for quick property access
            $this->propertiesCache[$id] = $properties;

            return $object;
        } catch (\Throwable $e) {
            // Return null on failure - calling code should handle gracefully
            // In production, you might want to log this properly via your logging system
            return null;
        }
    }

    /**
     * Get repository info (minimal implementation)
     */
    public function getRepositoryInfo(): array
    {
        return [
            'id' => $this->optgovSession->getRepositoryId(),
            'cmisVersionSupported' => '1.1'
        ];
    }

    /**
     * Create object from CMIS data
     *
     * @return Document|Folder
     */
    private function createObjectFromData(array $data)
    {
        $properties = $data['properties'] ?? [];
        $baseTypeId = $properties['cmis:baseTypeId']['value'] ?? '';

        if ('cmis:folder' === $baseTypeId) {
            return $this->createFolderFromData($data);
        }

        return $this->createDocumentFromData($data);
    }

    /**
     * Create folder from CMIS data
     */
    private function createFolderFromData(array $data): Folder
    {
        $folder = new Folder($this);
        $properties = $data['properties'] ?? [];

        $folder->setId($properties['cmis:objectId']['value'] ?? '');
        $folder->setName($properties['cmis:name']['value'] ?? '');
        $folder->setProperties($properties);

        return $folder;
    }

    /**
     * Create document from CMIS data
     */
    private function createDocumentFromData(array $data): Document
    {
        $document = new Document($this);
        $properties = $data['properties'] ?? [];

        $document->setId($properties['cmis:objectId']['value'] ?? '');
        $document->setName($properties['cmis:name']['value'] ?? '');
        $document->setProperties($properties);

        return $document;
    }

    /**
     * Get the underlying optigov session
     */
    public function getOptgovSession(): OptgovSession
    {
        return $this->optgovSession;
    }

    /**
     * Create an ObjectId from string
     */
    public function createObjectId(string $id): ObjectId
    {
        return new ObjectId($id);
    }

    /**
     * Get cached children for a folder
     */
    public function getCachedChildren(string $folderId): ?array
    {
        if (isset($this->childrenCache[$folderId])) {
            return $this->childrenCache[$folderId];
        }

        return null;
    }

    /**
     * Cache children for a folder
     */
    public function setCachedChildren(string $folderId, array $children): void
    {
        $this->childrenCache[$folderId] = $children;
    }

    /**
     * Get cached parents for an object
     */
    public function getCachedParents(string $objectId): ?array
    {
        if (isset($this->parentsCache[$objectId])) {
            return $this->parentsCache[$objectId];
        }

        return null;
    }

    /**
     * Cache parents for an object
     */
    public function setCachedParents(string $objectId, array $parents): void
    {
        $this->parentsCache[$objectId] = $parents;
    }

    /**
     * Clear specific object from cache
     */
    public function removeObjectFromCache(string|ObjectId $objectId): void
    {
        $id = is_string($objectId) ? $objectId : $objectId->getId();

        // Remove from all caches
        unset($this->objectCache[$id]);
        unset($this->propertiesCache[$id]);
        unset($this->childrenCache[$id]);
        unset($this->parentsCache[$id]);
        unset($this->contentStreamCache[$id]);
    }

    /**
     * Clear all caches
     */
    public function clearCache(): void
    {
        $this->objectCache = [];
        $this->childrenCache = [];
        $this->parentsCache = [];
        $this->propertiesCache = [];
        $this->contentStreamCache = [];
        $this->rootFolder = null;
    }

    /**
     * Cache content stream metadata (not the actual content)
     */
    public function setCachedContentStreamMetadata(string $objectId, array $metadata): void
    {
        $this->contentStreamCache[$objectId] = $metadata;
    }

    /**
     * Get cached content stream metadata
     */
    public function getCachedContentStreamMetadata(string $objectId): ?array
    {
        if (isset($this->contentStreamCache[$objectId])) {
            return $this->contentStreamCache[$objectId];
        }

        return null;
    }
}
