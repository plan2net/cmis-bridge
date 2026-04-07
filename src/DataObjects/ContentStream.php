<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\DataObjects;

/**
 * Immutable value object representing a CMIS content stream.
 */
readonly class ContentStream
{
    public function __construct(private string $contents) {}

    public function getContents(): string
    {
        return $this->contents;
    }

    public function getLength(): int
    {
        return strlen($this->contents);
    }

    public function __toString(): string
    {
        return $this->contents;
    }
}
