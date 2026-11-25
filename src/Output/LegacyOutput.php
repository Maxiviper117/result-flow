<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Output;

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Output\Serialization;

/** Explicitly opt into the v1 serialization shape, including metadata. */
final class LegacyOutput
{
    /**
     * @param  Result<mixed, mixed>  $result
     * @return array{ok: bool, value: mixed, error: mixed, meta: array<string, mixed>}
     */
    public static function toArray(Result $result): array
    {
        return Serialization::toArray($result);
    }

    /** @param Result<mixed, mixed> $result */
    public static function toJson(Result $result, int $options = 0): string
    {
        return Serialization::toJson($result, $options);
    }

    /** @param Result<mixed, mixed> $result */
    public static function toXml(Result $result, string $rootElement = 'result'): string
    {
        return Serialization::toXml($result, $rootElement);
    }
}
