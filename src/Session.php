<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge;

use CMIS\Http\Request;
use CMIS\Session\Session as OptgovSession;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Plan2net\CmisBridge\Data\ObjectId;
use Plan2net\CmisBridge\DataObjects\Document;
use Plan2net\CmisBridge\DataObjects\Folder;

/**
 * Bridge class that provides dkd/php-cmis Session interface using optigov/php-cmis-client
 */
class Session
{
    private readonly OptgovSession $optgovSession;
    /** Guzzle client with auth + timeout pre-configured, used for all HTTP requests. */
    private readonly GuzzleClient $httpClient;
    /**
     * @var array<string, mixed>
     */
    private readonly array $parameters;

    /**
     * @var array<string, Document|Folder>
     */
    private array $objectCache = [];
    /**
     * @var array<string, array<int, Document|Folder>>
     */
    private array $childrenCache = [];
    /**
     * @var array<string, array<int, Folder>>
     */
    private array $parentsCache = [];
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $propertiesCache = [];
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $contentStreamCache = [];

    private ?Folder $rootFolder = null;

    public function __construct(OptgovSession $optgovSession, array $parameters, GuzzleClient $httpClient)
    {
        $this->optgovSession = $optgovSession;
        $this->parameters = $parameters;
        $this->httpClient = $httpClient;
    }

    /**
     * Get the root folder of the repository.
     *
     * @throws GuzzleException
     * @throws \RuntimeException when the CMIS response is malformed
     */
    public function getRootFolder(): Folder
    {
        if (null === $this->rootFolder) {
            $browserUrl = $this->optgovSession->getUrl();
            $rootUrl = $browserUrl . '/root';

            $request = new Request($rootUrl);
            $request->addUrlParameter('cmisselector', 'object');

            $response = $this->httpClient->get($request->getUrl());
            $data = CmisResponseParser::decodeJsonResponse((string) $response->getBody());

            $this->rootFolder = $this->createFolderFromData($data);
        }

        return $this->rootFolder;
    }

    /**
     * Get a CMIS object by its ObjectId with caching.
     *
     * Returns null only when the repository reports the object does not exist
     * (missing `properties` key). Network errors and malformed responses throw.
     *
     * @throws GuzzleException
     * @throws \RuntimeException when the CMIS response is malformed
     *
     * @return Document|Folder|null
     */
    public function getObject(ObjectId $objectId): Document|Folder|null
    {
        $id = $objectId->getId();

        if (isset($this->objectCache[$id])) {
            return $this->objectCache[$id];
        }

        $browserUrl = $this->optgovSession->getUrl();
        $rootUrl = $browserUrl . '/root';

        $request = new Request($rootUrl);
        $request->addUrlParameter('objectId', $id)
               ->addUrlParameter('cmisselector', 'object')
               ->addUrlParameter('succinct', 'false');

        $response = $this->httpClient->get($request->getUrl());
        $data = CmisResponseParser::decodeJsonResponse((string) $response->getBody());

        if (!isset($data['properties'])) {
            return null;
        }

        $properties = $data['properties'];
        $objectType = $properties['cmis:objectTypeId']['value'] ?? '';

        $object = CmisResponseParser::isFolderType($objectType)
            ? new Folder($this)
            : new Document($this);

        $object->setId($properties['cmis:objectId']['value']);
        $object->setName($properties['cmis:name']['value'] ?? '');
        $object->setProperties($properties);

        $this->objectCache[$id] = $object;
        $this->propertiesCache[$id] = $properties;

        return $object;
    }

    /**
     * Get repository info (minimal implementation)
     *
     * @return array<string, string>
     */
    public function getRepositoryInfo(): array
    {
        return [
            'id' => $this->optgovSession->getRepositoryId(),
            'cmisVersionSupported' => '1.1',
        ];
    }

    /**
     * Create object from CMIS data
     *
     * @param array<string, mixed> $data
     *
     * @return Document|Folder
     */
    private function createObjectFromData(array $data): Document|Folder
    {
        $properties = $data['properties'] ?? [];
        $baseTypeId = $properties['cmis:baseTypeId']['value'] ?? '';

        if ('cmis:folder' === $baseTypeId) {
            return $this->createFolderFromData($data);
        }

        return $this->createDocumentFromData($data);
    }

    /**
     * @param array<string, mixed> $data
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
     * @param array<string, mixed> $data
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
     * @return array<int, Document|Folder>|null
     */
    public function getCachedChildren(string $folderId): ?array
    {
        return $this->childrenCache[$folderId] ?? null;
    }

    /**
     * @param array<int, Document|Folder> $children
     */
    public function setCachedChildren(string $folderId, array $children): void
    {
        $this->childrenCache[$folderId] = $children;
    }

    /**
     * @return array<int, Folder>|null
     */
    public function getCachedParents(string $objectId): ?array
    {
        return $this->parentsCache[$objectId] ?? null;
    }

    /**
     * @param array<int, Folder> $parents
     */
    public function setCachedParents(string $objectId, array $parents): void
    {
        $this->parentsCache[$objectId] = $parents;
    }

    public function removeObjectFromCache(string|ObjectId $objectId): void
    {
        $id = is_string($objectId) ? $objectId : $objectId->getId();

        unset(
            $this->objectCache[$id],
            $this->propertiesCache[$id],
            $this->childrenCache[$id],
            $this->parentsCache[$id],
            $this->contentStreamCache[$id],
        );
    }

    public function clearCache(): void
    {
        $this->objectCache = [];
        $this->childrenCache = [];
        $this->parentsCache = [];
        $this->propertiesCache = [];
        $this->contentStreamCache = [];
        $this->rootFolder = null;
    }

    public function setCachedContentStreamMetadata(string $objectId, array $metadata): void
    {
        $this->contentStreamCache[$objectId] = $metadata;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCachedContentStreamMetadata(string $objectId): ?array
    {
        return $this->contentStreamCache[$objectId] ?? null;
    }
}
