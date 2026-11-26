<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Output;

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Output\Serialization as Serializer;

/** Serialize results, including their metadata. */
final class Serialization
{
    /**
     * @param  Result<mixed, mixed>  $result
     * @return array{ok: bool, value: mixed, error: mixed, meta: array<string, mixed>}
     *
     * @throws \LengthException When a structured cause exceeds its traversal limits.
     */
    public static function toArray(Result $result): array
    {
        return Serializer::toArray($result);
    }

    /**
     * @param  Result<mixed, mixed>  $result
     *
     * @throws \InvalidArgumentException When partial output is requested.
     * @throws \JsonException When encoding fails.
     * @throws \LengthException When a structured cause exceeds its traversal limits.
     */
    public static function toJson(Result $result, int $options = 0): string
    {
        return Serializer::toJson($result, $options);
    }

    /**
     * @param  Result<mixed, mixed>  $result
     *
     * @throws \InvalidArgumentException When the data contains invalid XML text or recursive or excessively nested arrays.
     * @throws \LengthException When a structured cause exceeds its traversal limits.
     * @throws \RuntimeException When SimpleXML is unavailable.
     */
    public static function toXml(Result $result, string $rootElement = 'result'): string
    {
        return Serializer::toXml($result, $rootElement);
    }
}
