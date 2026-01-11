<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support;

use Maxiviper117\ResultFlow\Result;

/**
 * @internal
 */
final class ResultSerialization
{
    /**
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

    public static function toJson(Result $result, int $options = 0): string
    {
        return json_encode(self::toArray($result), $options | JSON_THROW_ON_ERROR);
    }

    public static function toXml(Result $result, string $rootElement = 'result'): string
    {
        $xml = new \SimpleXMLElement("<$rootElement/>");
        self::arrayToXml(self::toArray($result), $xml);

        return (string) $xml->asXML();
    }

    private static function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            $key = is_numeric($key) ? "item$key" : $key;
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                self::arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }
    }
}
