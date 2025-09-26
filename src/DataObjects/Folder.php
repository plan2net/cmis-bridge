<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\DataObjects;

use Plan2net\CmisBridge\Session;

/**
 * Bridge class that provides dkd/php-cmis Folder interface using optigov/php-cmis-client
 */
class Folder
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
     * Get folder ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set folder ID
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Get folder name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set folder name
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
     * Get children (files and folders in this folder)
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getChildren(): array
    {
        // Check cache first
        $cachedChildren = $this->session->getCachedChildren($this->id);
        if (null !== $cachedChildren) {
            return $cachedChildren;
        }

        try {
            // Use the same URL pattern as dkd/php-cmis NavigationService
            $browserUrl = $this->session->getOptgovSession()->getUrl();
            $rootUrl = $browserUrl . '/root';

            $request = new \CMIS\Http\Request($rootUrl);
            $request->addUrlParameter('objectId', $this->id)
                   ->addUrlParameter('cmisselector', 'children')
                   ->addUrlParameter('succinct', 'false');

            $response = $this->session->getOptgovSession()->getHttpClient()->get($request);
            $responseBody = (string) $response->getBody();
            $data = json_decode($responseBody, true);

            $children = [];

            // Handle the response structure - could be objects or single object
            $objectsData = [];
            if (isset($data['objects'])) {
                $objectsData = $data['objects'];
            } elseif (isset($data['object'])) {
                $objectsData = [$data]; // Single object response
            } elseif (isset($data[0])) {
                $objectsData = $data; // Array of objects directly
            }

            foreach ($objectsData as $childData) {
                $childObject = $childData['object'] ?? $childData;
                $childProperties = $childObject['properties'] ?? [];

                if (!empty($childProperties['cmis:objectId']['value'])) {
                    $objectType = $childProperties['cmis:objectTypeId']['value'] ?? '';

                    if (false !== strpos($objectType, 'cmis:folder')) {
                        $child = new Folder($this->session);
                    } else {
                        $child = new Document($this->session);
                    }

                    $child->setId($childProperties['cmis:objectId']['value']);
                    $child->setName($childProperties['cmis:name']['value'] ?? '');
                    $propertiesArray = is_array($childProperties) ? $childProperties : (array) $childProperties;
                    $child->setProperties($propertiesArray);
                    $children[] = $child;
                }
            }

            // Cache the results
            $this->session->setCachedChildren($this->id, $children);

            return $children;
        } catch (\Throwable $e) {
            // Return empty array on failure - calling code should handle gracefully
            // In production, you might want to log this properly via your logging system
            return [];
        }
    }

    /**
     * Get parent ID - required by AlfrescoDriver
     */
    public function getParentId(): ?string
    {
        return $this->getPropertyValue('cmis:parentId');
    }
}
