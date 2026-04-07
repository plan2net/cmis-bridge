<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\Data;

/**
 * Immutable value object representing a CMIS object identifier.
 */
readonly class ObjectId
{
    public function __construct(private string $id) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
