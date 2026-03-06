<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Output;

use Maxiviper117\ResultFlow\Result;

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
     */
    public static function toArray(Result $result): array
    {
        return [
            'ok' => $result->isOk(),
            'value' => $result->value(),
            'error' => $result->error(),
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
     * @throws \JsonException
     */
    public static function toJson(Result $result, int $options = 0): string
    {
        return json_encode(self::toArray($result), $options | JSON_THROW_ON_ERROR);
    }

    /**
     * Convert the Result to XML.
     *
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     */
    public static function toXml(Result $result, string $rootElement = 'result'): string
    {
        $xml = new \SimpleXMLElement('<'.self::normalizeXmlElementName($rootElement).'/>');
        self::arrayToXml(self::toArray($result), $xml);

        return (string) $xml->asXML();
    }

    /**
     * Recursively write array data to an XML element.
     *
     * @param  array<mixed, mixed>  $data
     */
    private static function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            $key = self::normalizeXmlElementName((string) $key);
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                self::arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars(self::stringifyValue($value)));
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
        if ($name !== '' && ctype_digit($name)) {
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
