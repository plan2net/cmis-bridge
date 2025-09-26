<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Base test class for Plan2net CMIS Bridge tests
 */
abstract class BridgeTestCase extends TestCase
{
    protected function tearDown(): void
    {
        /**
         * @psalm-suppress UndefinedClass
         */
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * Create mock CMIS properties array
     */
    protected function createMockCmisProperties(array $overrides = []): array
    {
        $defaults = [
            'cmis:objectId' => [
                'id' => 'cmis:objectId',
                'localName' => 'objectId',
                'displayName' => 'Object Id',
                'queryName' => 'cmis:objectId',
                'type' => 'id',
                'cardinality' => 'single',
                'value' => 'test-object-123'
            ],
            'cmis:name' => [
                'id' => 'cmis:name',
                'localName' => 'name',
                'displayName' => 'Name',
                'queryName' => 'cmis:name',
                'type' => 'string',
                'cardinality' => 'single',
                'value' => 'Test Object'
            ],
            'cmis:creationDate' => [
                'id' => 'cmis:creationDate',
                'localName' => 'creationDate',
                'displayName' => 'Creation Date',
                'queryName' => 'cmis:creationDate',
                'type' => 'datetime',
                'cardinality' => 'single',
                'value' => 1669366179934  // Unix timestamp in milliseconds
            ],
            'cmis:lastModificationDate' => [
                'id' => 'cmis:lastModificationDate',
                'localName' => 'lastModificationDate',
                'displayName' => 'Last Modification Date',
                'queryName' => 'cmis:lastModificationDate',
                'type' => 'datetime',
                'cardinality' => 'single',
                'value' => 1669366199000  // Unix timestamp in milliseconds
            ],
            'cmis:createdBy' => [
                'id' => 'cmis:createdBy',
                'localName' => 'createdBy',
                'displayName' => 'Created By',
                'queryName' => 'cmis:createdBy',
                'type' => 'string',
                'cardinality' => 'single',
                'value' => 'testuser'
            ]
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create mock document properties
     */
    protected function createMockDocumentProperties(array $overrides = []): array
    {
        $documentDefaults = [
            'cmis:objectTypeId' => [
                'id' => 'cmis:objectTypeId',
                'value' => 'cmis:document'
            ],
            'cmis:contentStreamLength' => [
                'id' => 'cmis:contentStreamLength',
                'value' => 1024
            ],
            'cmis:contentStreamMimeType' => [
                'id' => 'cmis:contentStreamMimeType',
                'value' => 'application/pdf'
            ],
            'cmis:versionSeriesId' => [
                'id' => 'cmis:versionSeriesId',
                'value' => 'version-123'
            ]
        ];

        return $this->createMockCmisProperties(array_merge($documentDefaults, $overrides));
    }

    /**
     * Create mock folder properties
     */
    protected function createMockFolderProperties(array $overrides = []): array
    {
        $folderDefaults = [
            'cmis:objectTypeId' => [
                'id' => 'cmis:objectTypeId',
                'value' => 'cmis:folder'
            ],
            'cmis:parentId' => [
                'id' => 'cmis:parentId',
                'value' => 'parent-folder-123'
            ]
        ];

        return $this->createMockCmisProperties(array_merge($folderDefaults, $overrides));
    }
}
