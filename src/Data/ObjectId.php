<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\Data;

/**
 * Bridge class for ObjectId to maintain compatibility with dkd/php-cmis interface
 */
class ObjectId
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Get the object ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the object ID
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->id;
    }
}
