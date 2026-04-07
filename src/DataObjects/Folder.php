<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\DataObjects;

use GuzzleHttp\Exception\GuzzleException;
use Plan2net\CmisBridge\CmisResponseParser;
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

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function getPropertyValue(string $propertyId): mixed
    {
        return $this->properties[$propertyId]['value'] ?? null;
    }

    public function getCreationDate(): ?\DateTimeImmutable
    {
        return CmisResponseParser::parseCmisDate($this->getPropertyValue('cmis:creationDate'));
    }

    public function getLastModificationDate(): ?\DateTimeImmutable
    {
        return CmisResponseParser::parseCmisDate($this->getPropertyValue('cmis:lastModificationDate'));
    }

    public function getCreatedBy(): ?string
    {
        return $this->getPropertyValue('cmis:createdBy');
    }

    /**
     * Get children (files and subfolders).
     *
     * @throws GuzzleException
     * @throws \RuntimeException when the CMIS response is malformed
     *
     * @return array<int, Document|Folder>
     */
    public function getChildren(): array
    {
        $cachedChildren = $this->session->getCachedChildren($this->id);
        if (null !== $cachedChildren) {
            return $cachedChildren;
        }

        $browserUrl = $this->session->getOptgovSession()->getUrl();
        $rootUrl = $browserUrl . '/root';

        $request = new \CMIS\Http\Request($rootUrl);
        $request->addUrlParameter('objectId', $this->id)
               ->addUrlParameter('cmisselector', 'children')
               ->addUrlParameter('succinct', 'false');

        $response = $this->session->getOptgovSession()->getHttpClient()->get($request);
        $data = CmisResponseParser::decodeJsonResponse((string) $response->getBody());

        $children = [];
        foreach (CmisResponseParser::extractObjects($data) as $childData) {
            $childObject = $childData['object'] ?? $childData;
            $childProperties = $childObject['properties'] ?? [];

            if (empty($childProperties['cmis:objectId']['value'])) {
                continue;
            }

            $objectType = $childProperties['cmis:objectTypeId']['value'] ?? '';
            $child = CmisResponseParser::isFolderType($objectType)
                ? new Folder($this->session)
                : new Document($this->session);

            $child->setId($childProperties['cmis:objectId']['value']);
            $child->setName($childProperties['cmis:name']['value'] ?? '');
            $child->setProperties($childProperties);
            $children[] = $child;
        }

        $this->session->setCachedChildren($this->id, $children);

        return $children;
    }

    public function getParentId(): ?string
    {
        return $this->getPropertyValue('cmis:parentId');
    }
}
