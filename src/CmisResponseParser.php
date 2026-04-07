<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge;

/**
 * Shared utilities for parsing CMIS Browser Binding JSON responses.
 */
final class CmisResponseParser
{
    /**
     * Decode a raw HTTP response body as a CMIS JSON object.
     *
     * @throws \RuntimeException when the body is not valid JSON or not an array
     */
    public static function decodeJsonResponse(string $body): array
    {
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new \RuntimeException(
                'Invalid CMIS response: expected JSON object, got: ' . substr($body, 0, 200)
            );
        }

        return $data;
    }

    /**
     * Normalise the three different "list of objects" shapes that the CMIS
     * browser binding may return:
     *   - {"objects": [...]}   (getChildren / getParents standard)
     *   - {"object": {...}}    (single-object response)
     *   - [{...}, ...]         (direct array)
     *
     * @return array<int, array<string, mixed>>
     */
    public static function extractObjects(array $data): array
    {
        if (isset($data['objects'])) {
            return $data['objects'];
        }
        if (isset($data['object'])) {
            return [$data];
        }
        if (isset($data[0])) {
            return $data;
        }

        return [];
    }

    /**
     * Determine whether a CMIS objectTypeId string refers to a folder.
     */
    public static function isFolderType(string $objectTypeId): bool
    {
        return str_contains($objectTypeId, 'cmis:folder');
    }

    /**
     * Parse a CMIS date value into a \DateTimeImmutable.
     *
     * Alfresco returns dates as Unix timestamps in milliseconds (integer) or
     * as ISO 8601 strings. Returns null when the value is absent or unparseable.
     */
    public static function parseCmisDate(mixed $timestamp): ?\DateTimeImmutable
    {
        if (!$timestamp) {
            return null;
        }

        try {
            if (is_numeric($timestamp)) {
                return new \DateTimeImmutable('@' . intval((int) $timestamp / 1000));
            }

            return new \DateTimeImmutable((string) $timestamp);
        } catch (\Exception) {
            return null;
        }
    }
}
