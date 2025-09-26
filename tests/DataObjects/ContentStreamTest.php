<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\Tests\DataObjects;

use PHPUnit\Framework\TestCase;
use Plan2net\CmisBridge\DataObjects\ContentStream;

/**
 * Test for ContentStream class
 */
class ContentStreamTest extends TestCase
{
    public function testConstructorSetsContent(): void
    {
        $content = 'This is test content for the stream';
        $contentStream = new ContentStream($content);

        $this->assertSame($content, $contentStream->getContents());
    }

    public function testGetContentReturnsCorrectValue(): void
    {
        $content = 'Binary content simulation: ' . bin2hex(random_bytes(32));
        $contentStream = new ContentStream($content);

        $this->assertEquals($content, $contentStream->getContents());
    }

    public function testConstructorWithEmptyContent(): void
    {
        $contentStream = new ContentStream('');

        $this->assertSame('', $contentStream->getContents());
    }

    public function testConstructorWithBinaryContent(): void
    {
        $binaryContent = "\x00\x01\x02\x03\xFF\xFE\xFD";
        $contentStream = new ContentStream($binaryContent);

        $this->assertSame($binaryContent, $contentStream->getContents());
    }

    public function testConstructorWithLargeContent(): void
    {
        $largeContent = str_repeat('Large content test string. ', 1000);
        $contentStream = new ContentStream($largeContent);

        $this->assertSame($largeContent, $contentStream->getContents());
        $this->assertGreaterThan(25000, strlen($contentStream->getContents()));
    }

    public function testGetLength(): void
    {
        $content = 'Test content with known length';
        $contentStream = new ContentStream($content);

        $this->assertSame(strlen($content), $contentStream->getLength());
    }

    public function testToStringMagicMethod(): void
    {
        $content = 'Test string conversion';
        $contentStream = new ContentStream($content);

        $this->assertSame($content, (string) $contentStream);
    }

    public function testContentStreamIsImmutable(): void
    {
        $originalContent = 'Original content';
        $contentStream = new ContentStream($originalContent);

        // Verify original content
        $this->assertSame($originalContent, $contentStream->getContents());

        // Since there's no setter, content should remain unchanged
        $this->assertSame($originalContent, $contentStream->getContents());
    }
}
