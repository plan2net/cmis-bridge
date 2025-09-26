<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\Tests\Data;

use PHPUnit\Framework\TestCase;
use Plan2net\CmisBridge\Data\ObjectId;

/**
 * Test for ObjectId class
 */
class ObjectIdTest extends TestCase
{
    public function testConstructorSetsId(): void
    {
        $id = 'test-object-123';
        $objectId = new ObjectId($id);

        $this->assertSame($id, $objectId->getId());
    }

    public function testGetIdReturnsCorrectValue(): void
    {
        $id = 'another-test-id-456';
        $objectId = new ObjectId($id);

        $this->assertEquals($id, $objectId->getId());
    }

    public function testConstructorWithEmptyString(): void
    {
        $objectId = new ObjectId('');

        $this->assertSame('', $objectId->getId());
    }

    public function testConstructorWithLongId(): void
    {
        $longId = 'workspace://SpacesStore/12345678-1234-1234-1234-123456789abc';
        $objectId = new ObjectId($longId);

        $this->assertSame($longId, $objectId->getId());
    }

    public function testToStringMagicMethod(): void
    {
        $id = 'test-string-representation';
        $objectId = new ObjectId($id);

        $this->assertSame($id, (string) $objectId);
    }
}
