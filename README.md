# Plan2net CMIS Bridge

A bridge library that provides [dkd/php-cmis](https://github.com/dkd/php-cmis-client) interface compatibility using [optigov/php-cmis-client](https://github.com/optiGov/php-cmis-client) as the underlying HTTP client.

## Purpose

This package was created to migrate TYPO3 extensions from the unmaintained [`dkd/php-cmis`](https://github.com/dkd/php-cmis-client) library to the actively maintained [`optigov/php-cmis-client`](https://github.com/optiGov/php-cmis-client) while preserving API compatibility.

## Features

- **API Compatibility**: Drop-in replacement for dkd/php-cmis classes (read-only operations)
- **HTTP Client**: Uses [optigov/php-cmis-client](https://github.com/optiGov/php-cmis-client) for modern HTTP handling
- **CMIS 1.1**: Supports CMIS Browser binding
- **Object Model**: Document and Folder objects with property access
- **DateTime Handling**: Proper parsing of Alfresco timestamp formats
- **Error Handling**: Robust error handling with fallback behaviors
- **Enhanced Caching**: Multi-level caching system for optimal performance
  - Object cache for CMIS objects
  - Children cache for folder contents
  - Parent cache for object relationships
  - Properties cache for metadata
  - Content stream metadata cache

## Installation

Add to your `composer.json`:

```json
{
    "require": {
        "plan2net/cmis-bridge": "*"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/plan2net/cmis-bridge.git"
        }
    ]
}
```

Then run:

```bash
composer install
```

## Usage

### Basic Usage

```php
use Plan2net\CmisBridge\SessionFactory;

// Create session factory
$factory = new SessionFactory();

// Create session with CMIS endpoint
$session = $factory->createSession($url, $username, $password, $repositoryId);

// Get objects
$objectId = $session->createObjectId('your-object-id');
$object = $session->getObject($objectId);

// Access properties
echo $object->getName();
echo $object->getCreationDate()->format('Y-m-d H:i:s');
```

### Document Operations

```php
// Get document content
$document = $session->getObject($documentObjectId);
$contentStream = $document->getContentStream();
$content = $contentStream->getContent();

// Get metadata
$size = $document->getContentStreamLength();
$mimeType = $document->getContentStreamMimeType();
$modifiedDate = $document->getLastModificationDate();
```

### Folder Operations

```php
// Get folder children
$folder = $session->getObject($folderObjectId);
$children = $folder->getChildren();

foreach ($children as $child) {
    if ($child instanceof Document) {
        echo "File: " . $child->getName();
    } elseif ($child instanceof Folder) {
        echo "Folder: " . $child->getName();
    }
}
```

### Caching Operations

```php
// Clear specific object from cache
$session->removeObjectFromCache($objectId);

// Clear all caches
$session->clearCache();

// The bridge automatically caches:
// - CMIS objects (documents and folders)
// - Folder children lists
// - Object parent relationships  
// - Object properties
// - Content stream metadata
```

## Migration from [dkd/php-cmis](https://github.com/dkd/php-cmis-client)

Simply replace the import statements:

```php
// Old dkd/php-cmis imports
use Dkd\PhpCmis\SessionFactory;
use Dkd\PhpCmis\Session;

// New Plan2net bridge imports
use Plan2net\CmisBridge\SessionFactory;
use Plan2net\CmisBridge\Session;
```

The API remains the same, ensuring seamless migration.

## Limitations

**Important**: This bridge package was specifically designed for read-only operations and does not implement the full feature set of the original dkd/php-cmis library. It focuses on the core functionality needed for TYPO3 FAL (File Abstraction Layer) integration.

### Supported Operations ✅

- **Folder Browsing**: Navigate folder hierarchies and retrieve folder contents
- **Metadata Retrieval**: Access CMIS object properties (creation date, modification date, size, MIME type, etc.)
- **File Retrieval**: Download document content streams
- **Object Navigation**: Get parent/child relationships between folders and documents
- **Authentication**: Basic authentication with username/password
- **Caching**: Multi-level caching for performance optimization
- **Property Access**: Read CMIS properties via `getPropertyValue()`

### Not Supported ❌

- **Write Operations**: Creating, updating, or deleting documents/folders
- **Version Management**: Document versioning, check-in/check-out operations  
- **Access Control**: ACL (Access Control List) management
- **Relationships**: CMIS relationship objects
- **Policies**: CMIS policy management
- **Type Definitions**: Custom CMIS type creation or modification
- **Bulk Operations**: Batch document operations
- **Advanced Queries**: CMIS-SQL queries beyond basic object retrieval
- **Content Manipulation**: Document transformation or content modification
- **Workflow Integration**: CMIS workflow operations

### Use Case Focus

This bridge was created specifically for:

- TYPO3 FAL storage drivers requiring read-only access to Alfresco
- Content management systems that browse and display documents
- Applications that need to retrieve and cache document metadata
- Systems requiring file download functionality from CMIS repositories

If you need full CMIS write capabilities, consider using the [`optigov/php-cmis-client`](https://github.com/optiGov/php-cmis-client) library directly or extending this bridge with additional functionality.

## Architecture

- **SessionFactory**: Creates CMIS sessions with authentication
- **Session**: Manages CMIS repository connections and object retrieval
- **ObjectId**: Represents CMIS object identifiers
- **Document**: File objects with content and metadata access
- **Folder**: Directory objects with child enumeration
- **ContentStream**: File content wrapper

## Dependencies

- **[optigov/php-cmis-client](https://github.com/optiGov/php-cmis-client)**: HTTP client for CMIS operations
- **guzzlehttp/guzzle**: HTTP client library
- **PHP 8.1+**: Modern PHP features

## Compatibility

- **CMIS 1.1 Browser Binding**: Read-only operations via HTTP
- **Alfresco Community/Enterprise**: Tested with Alfresco repositories  
- **TYPO3 12+ FAL Integration**: Designed for TYPO3 File Abstraction Layer
- **Read-Only Scenarios**: Perfect for content browsing and file serving
- **API Compatibility**: Subset of dkd/php-cmis interface for supported operations

## License

This package is licensed under the MIT License.
See the [LICENSE](LICENSE) file for the full license text.