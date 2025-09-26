<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\DataObjects;

/**
 * Bridge class for ContentStream to maintain compatibility with dkd/php-cmis interface
 */
class ContentStream
{
    private string $contents;

    public function __construct(string $contents)
    {
        $this->contents = $contents;
    }

    /**
     * Get the content as string
     */
    public function getContents(): string
    {
        return $this->contents;
    }

    /**
     * Get content length
     */
    public function getLength(): int
    {
        return strlen($this->contents);
    }

    /**
     * Get stream contents (alias for getContents)
     */
    public function __toString(): string
    {
        return $this->contents;
    }
}
