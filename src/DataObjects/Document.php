<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\DataObjects;

use GuzzleHttp\Exception\GuzzleException;
use Plan2net\CmisBridge\CmisResponseParser;
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

    public function getContentStreamLength(): ?int
    {
        return $this->getPropertyValue('cmis:contentStreamLength');
    }

    public function getContentStreamMimeType(): ?string
    {
        return $this->getPropertyValue('cmis:contentStreamMimeType');
    }

    public function getVersionSeriesId(): ?string
    {
        return $this->getPropertyValue('cmis:versionSeriesId');
    }

    /**
     * Get parent folders.
     *
     * @throws GuzzleException
     * @throws \RuntimeException when the CMIS response is malformed
     *
     * @return Folder[]
     */
    public function getParents(): array
    {
        $cachedParents = $this->session->getCachedParents($this->id);
        if (null !== $cachedParents) {
            return $cachedParents;
        }

        $browserUrl = $this->session->getOptgovSession()->getUrl();
        $rootUrl = $browserUrl . '/root';

        $request = new \CMIS\Http\Request($rootUrl);
        $request->addUrlParameter('objectId', $this->id)
               ->addUrlParameter('cmisselector', 'parents')
               ->addUrlParameter('succinct', 'false');

        $response = $this->session->getOptgovSession()->getHttpClient()->get($request);
        $data = CmisResponseParser::decodeJsonResponse((string) $response->getBody());

        $parents = [];
        foreach (CmisResponseParser::extractObjects($data) as $parentData) {
            $parentObject = $parentData['object'] ?? $parentData;
            $parentProperties = $parentObject['properties'] ?? [];

            if (!empty($parentProperties['cmis:objectId']['value'])) {
                $folder = new Folder($this->session);
                $folder->setId($parentProperties['cmis:objectId']['value']);
                $folder->setName($parentProperties['cmis:name']['value'] ?? '');
                $folder->setProperties($parentProperties);
                $parents[] = $folder;
            }
        }

        $this->session->setCachedParents($this->id, $parents);

        return $parents;
    }

    /**
     * Get content stream.
     *
     * @throws GuzzleException
     */
    public function getContentStream(): ContentStream
    {
        $browserUrl = $this->session->getOptgovSession()->getUrl();
        $rootUrl = $browserUrl . '/root';

        $request = new \CMIS\Http\Request($rootUrl);
        $request->addUrlParameter('objectId', $this->id)
               ->addUrlParameter('cmisselector', 'content');

        $response = $this->session->getOptgovSession()->getHttpClient()->get($request);

        return new ContentStream((string) $response->getBody());
    }

    /**
     * Get parent ID (first parent folder).
     *
     * @throws GuzzleException
     * @throws \RuntimeException
     */
    public function getParentId(): ?string
    {
        $parents = $this->getParents();

        return $parents[0]?->getId() ?? null;
    }
}
