<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\Tests;

use PHPUnit\Framework\TestCase;
use Plan2net\CmisBridge\CmisResponseParser;

class CmisResponseParserTest extends TestCase
{
    // --- decodeJsonResponse ---

    public function testDecodeJsonResponseReturnsArray(): void
    {
        $result = CmisResponseParser::decodeJsonResponse('{"properties": {"cmis:name": {"value": "test"}}}');
        $this->assertSame('test', $result['properties']['cmis:name']['value']);
    }

    public function testDecodeJsonResponseThrowsOnInvalidJson(): void
    {
        $this->expectException(\RuntimeException::class);
        CmisResponseParser::decodeJsonResponse('not json at all');
    }

    public function testDecodeJsonResponseThrowsOnJsonArray(): void
    {
        // A JSON array (not an object) is not a valid CMIS response
        $this->expectException(\RuntimeException::class);
        CmisResponseParser::decodeJsonResponse('null');
    }

    public function testDecodeJsonResponseThrowsOnEmptyBody(): void
    {
        $this->expectException(\RuntimeException::class);
        CmisResponseParser::decodeJsonResponse('');
    }

    // --- extractObjects ---

    public function testExtractObjectsHandlesObjectsKey(): void
    {
        $data = ['objects' => [['object' => ['properties' => []]], ['object' => ['properties' => []]]]];
        $result = CmisResponseParser::extractObjects($data);
        $this->assertCount(2, $result);
    }

    public function testExtractObjectsHandlesSingleObjectKey(): void
    {
        $data = ['object' => ['properties' => ['cmis:objectId' => ['value' => '123']]]];
        $result = CmisResponseParser::extractObjects($data);
        $this->assertCount(1, $result);
        $this->assertSame($data, $result[0]);
    }

    public function testExtractObjectsHandlesDirectArray(): void
    {
        $data = [['properties' => []], ['properties' => []]];
        $result = CmisResponseParser::extractObjects($data);
        $this->assertCount(2, $result);
    }

    public function testExtractObjectsReturnsEmptyArrayForUnknownShape(): void
    {
        $result = CmisResponseParser::extractObjects(['someOtherKey' => 'value']);
        $this->assertSame([], $result);
    }

    // --- isFolderType ---

    public function testIsFolderTypeReturnsTrueForCmisFolder(): void
    {
        $this->assertTrue(CmisResponseParser::isFolderType('cmis:folder'));
    }

    public function testIsFolderTypeReturnsTrueForSubtype(): void
    {
        $this->assertTrue(CmisResponseParser::isFolderType('F:cmis:folder'));
    }

    public function testIsFolderTypeReturnsFalseForDocument(): void
    {
        $this->assertFalse(CmisResponseParser::isFolderType('cmis:document'));
    }

    public function testIsFolderTypeReturnsFalseForEmptyString(): void
    {
        $this->assertFalse(CmisResponseParser::isFolderType(''));
    }

    // --- parseCmisDate ---

    public function testParseCmisDateWithMillisecondTimestamp(): void
    {
        $result = CmisResponseParser::parseCmisDate(1669366179934);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame(1669366179, $result->getTimestamp());
    }

    public function testParseCmisDateWithStringTimestamp(): void
    {
        $result = CmisResponseParser::parseCmisDate('1669366179934');
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame(1669366179, $result->getTimestamp());
    }

    public function testParseCmisDateWithIso8601String(): void
    {
        $result = CmisResponseParser::parseCmisDate('2022-11-25T12:30:00Z');
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('2022-11-25', $result->format('Y-m-d'));
    }

    public function testParseCmisDateReturnsNullForNull(): void
    {
        $this->assertNull(CmisResponseParser::parseCmisDate(null));
    }

    public function testParseCmisDateReturnsNullForEmptyString(): void
    {
        $this->assertNull(CmisResponseParser::parseCmisDate(''));
    }

    public function testParseCmisDateReturnsNullForInvalidString(): void
    {
        $this->assertNull(CmisResponseParser::parseCmisDate('not-a-date'));
    }

    public function testParseCmisDateReturnsNullForZero(): void
    {
        // 0 is falsy — treated as absent
        $this->assertNull(CmisResponseParser::parseCmisDate(0));
    }
}
