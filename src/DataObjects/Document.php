<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\DataObjects;

use GuzzleHttp\Exception\GuzzleException;
use Plan2net\CmisBridge\Session;

/**
 * Bridge class that provides dkd/php-cmis Document interface using optigov/php-cmis-client
 */
class Document
{
    private Session $session;
    private string $id = '';
    private string $name = '';
    /**
     * @var array<string, mixed>
     */
    private array $properties = [];

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Get document ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set document ID
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get document name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set document name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Set properties from CMIS data
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    /**
     * Get property value by property ID
     */
    public function getPropertyValue(string $propertyId): mixed
    {
        return $this->properties[$propertyId]['value'] ?? null;
    }

    /**
     * Get creation date
     */
    public function getCreationDate(): ?\DateTime
    {
        $timestamp = $this->getPropertyValue('cmis:creationDate');
        if (!$timestamp) {
            return null;
        }

        // Handle Unix timestamp in milliseconds (convert to seconds)
        if (is_numeric($timestamp)) {
            $timestampSeconds = intval($timestamp / 1000);

            return new \DateTime('@' . $timestampSeconds);
        }

        // Handle string datetime
        return new \DateTime($timestamp);
    }

    /**
     * Get last modification date
     */
    public function getLastModificationDate(): ?\DateTime
    {
        $timestamp = $this->getPropertyValue('cmis:lastModificationDate');
        if (!$timestamp) {
            return null;
        }

        // Handle Unix timestamp in milliseconds (convert to seconds)
        if (is_numeric($timestamp)) {
            $timestampSeconds = intval($timestamp / 1000);

            return new \DateTime('@' . $timestampSeconds);
        }

        // Handle string datetime
        return new \DateTime($timestamp);
    }

    /**
     * Get created by user
     */
    public function getCreatedBy(): ?string
    {
        return $this->getPropertyValue('cmis:createdBy');
    }

    /**
     * Get content stream length (file size)
     */
    public function getContentStreamLength(): ?int
    {
        return $this->getPropertyValue('cmis:contentStreamLength');
    }

    /**
     * Get content stream MIME type
     */
    public function getContentStreamMimeType(): ?string
    {
        return $this->getPropertyValue('cmis:contentStreamMimeType');
    }

    /**
     * Get version series ID
     */
    public function getVersionSeriesId(): ?string
    {
        return $this->getPropertyValue('cmis:versionSeriesId');
    }

    /**
     * Get parent folders - matches dkd/php-cmis implementation pattern
     *
     * @throws GuzzleException
     *
     * @return Folder[]
     */
    public function getParents(): array
    {
        // Check cache first
        $cachedParents = $this->session->getCachedParents($this->id);
        if (null !== $cachedParents) {
            return $cachedParents;
        }

        try {
            // Use the same URL pattern as dkd/php-cmis NavigationService
            $browserUrl = $this->session->getOptgovSession()->getUrl();
            $rootUrl = $browserUrl . '/root';

            $request = new \CMIS\Http\Request($rootUrl);
            $request->addUrlParameter('objectId', $this->id)
                   ->addUrlParameter('cmisselector', 'parents')
                   ->addUrlParameter('succinct', 'false'); // Match dkd default

            $response = $this->session->getOptgovSession()->getHttpClient()->get($request);
            $responseBody = (string) $response->getBody();
            $data = json_decode($responseBody, true);

            $parents = [];

            // Handle the response structure - could be objects or single object
            $objectsData = [];
            if (isset($data['objects'])) {
                $objectsData = $data['objects'];
            } elseif (isset($data['object'])) {
                $objectsData = [$data]; // Single object response
            } elseif (isset($data[0])) {
                $objectsData = $data; // Array of objects directly
            }

            foreach ($objectsData as $parentData) {
                $parentObject = $parentData['object'] ?? $parentData;
                $parentProperties = $parentObject['properties'] ?? [];

                if (!empty($parentProperties['cmis:objectId']['value'])) {
                    $folder = new Folder($this->session);
                    $folder->setId($parentProperties['cmis:objectId']['value']);
                    $folder->setName($parentProperties['cmis:name']['value'] ?? '');
                    $propertiesArray = is_array($parentProperties) ? $parentProperties : (array) $parentProperties;
                    $folder->setProperties($propertiesArray);
                    $parents[] = $folder;
                }
            }

            // Cache the results
            $this->session->setCachedParents($this->id, $parents);

            return $parents;
        } catch (\Throwable $e) {
            // Return empty array on failure - calling code should handle gracefully
            // In production, you might want to log this properly via your logging system
            return [];
        }
    }

    /**
     * Get content stream
     *
     * @throws GuzzleException
     */
    public function getContentStream(): ContentStream
    {
        // Use correct CMIS Browser binding endpoint for content
        $browserUrl = $this->session->getOptgovSession()->getUrl();
        $rootUrl = $browserUrl . '/root';

        $request = new \CMIS\Http\Request($rootUrl);
        $request->addUrlParameter('objectId', $this->id)
               ->addUrlParameter('cmisselector', 'content');

        $response = $this->session->getOptgovSession()->getHttpClient()->get($request);
        $content = (string) $response->getBody();

        return new ContentStream($content);
    }

    /**
     * Get parent ID - required by AlfrescoDriver
     */
    public function getParentId(): ?string
    {
        // Get first parent folder ID
        $parents = $this->getParents();
        if (!empty($parents)) {
            return $parents[0]->getId();
        }

        return null;
    }
}
