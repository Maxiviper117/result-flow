<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Output;

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\ResultError;

/**
 * Helpers for serializing Results to arrays and text formats.
 *
 * @internal
 */
final class Serialization
{
    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @return array{ok: bool, value: mixed, error: mixed, meta: array<string,mixed>}
     *
     * @throws \LengthException When a structured cause exceeds its traversal limits.
     */
    public static function toArray(Result $result): array
    {
        $error = $result->match(fn ($value) => null, fn ($error) => $error);

        if ($error instanceof ResultError) {
            $errorOut = $error->toArray();
        } else {
            $errorOut = $error;
        }

        return [
            'ok' => $result->isOk(),
            'value' => $result->match(fn ($value) => $value, fn ($error) => null),
            'error' => $errorOut,
            'meta' => $result->meta(),
        ];
    }

    /**
     * Convert the Result to JSON.
     *
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  int  $options  JSON encoding options
     *
     * @throws \InvalidArgumentException When partial output is requested.
     * @throws \JsonException
     * @throws \LengthException When a structured cause exceeds its traversal limits.
     */
    public static function toJson(Result $result, int $options = 0): string
    {
        if (($options & JSON_PARTIAL_OUTPUT_ON_ERROR) !== 0) {
            throw new \InvalidArgumentException('JSON_PARTIAL_OUTPUT_ON_ERROR is incompatible with throwing on encoding failure');
        }

        return json_encode(self::toArray($result), $options | JSON_THROW_ON_ERROR);
    }

    /**
     * Convert the Result to XML.
     *
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     *
     * @throws \InvalidArgumentException When the data contains invalid XML text or recursive or excessively nested arrays.
     * @throws \LengthException When a structured cause exceeds its traversal limits.
     * @throws \RuntimeException When SimpleXML is unavailable.
     */
    public static function toXml(Result $result, string $rootElement = 'result'): string
    {
        if (! class_exists(\SimpleXMLElement::class)) {
            throw new \RuntimeException('XML output requires the SimpleXML extension');
        }
        $xml = new \SimpleXMLElement('<'.self::normalizeXmlElementName($rootElement).'/>');
        $references = [];
        self::arrayToXml(self::toArray($result), $xml, 0, $references);

        return (string) $xml->asXML();
    }

    /**
     * Recursively write array data to an XML element.
     *
     * @param  array<array-key, mixed>  $data
     * @param  array<string, bool>  $references
     */
    private static function arrayToXml(array $data, \SimpleXMLElement $xml, int $depth, array &$references): void
    {
        if ($depth >= 128) {
            throw new \InvalidArgumentException('XML output exceeds maximum array depth');
        }

        foreach ($data as $key => $value) {
            $reference = is_array($value) ? \ReflectionReference::fromArrayElement($data, $key) : null;
            $referenceId = $reference?->getId();
            $key = self::normalizeXmlElementName((string) $key);
            if (is_array($value)) {
                if ($referenceId !== null && isset($references[$referenceId])) {
                    throw new \InvalidArgumentException('XML output contains a recursive array');
                }
                if ($referenceId !== null) {
                    $references[$referenceId] = true;
                }
                $subnode = $xml->addChild($key);
                self::arrayToXml($value, $subnode, $depth + 1, $references);
                if ($referenceId !== null) {
                    unset($references[$referenceId]);
                }
            } else {
                $text = self::stringifyValue($value);
                if (preg_match('/\A[\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]*\z/u', $text) !== 1) {
                    throw new \InvalidArgumentException('XML output contains invalid UTF-8 or XML 1.0 characters');
                }
                $xml->addChild($key, htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
            }
        }
    }

    /**
     * Convert arbitrary values to a safe string representation.
     */
    private static function stringifyValue(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        $json = json_encode($value);

        return is_string($json) ? $json : var_export($value, true);
    }

    /**
     * Normalize arbitrary keys into XML-safe element names.
     */
    private static function normalizeXmlElementName(string $name): string
    {
        if (preg_match('/\A[0-9]+\z/', $name) === 1) {
            return 'item'.$name;
        }

        $normalized = preg_replace('/[^A-Za-z0-9_-]+/', '_', $name) ?? '';
        $normalized = trim($normalized, '_');

        if ($normalized === '') {
            return 'item';
        }

        if (! preg_match('/^[A-Za-z_]/', $normalized) || str_starts_with(strtolower($normalized), 'xml')) {
            $normalized = 'item_'.$normalized;
        }

        return $normalized;
    }
}
