<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support;

use Maxiviper117\ResultFlow\Result;
use Throwable;

/**
 * @internal
 */
final class ResultDebug
{
    /**
     * @param  callable(mixed): mixed|null  $sanitizer
     * @return array{ok: bool, value_type: string|null, error_type: string|null, error_message: mixed, meta: mixed}
     */
    public static function toDebugArray(Result $result, ?callable $sanitizer = null): array
    {
        $sanitizer = $sanitizer ?? [self::class, 'defaultSanitizer'];
        $ok = $result->isOk();
        $error = $result->error();

        return [
            'ok' => $ok,
            'value_type' => $ok ? get_debug_type($result->value()) : null,
            'error_type' => ! $ok ? get_debug_type($error) : null,
            'error_message' => ! $ok && $error instanceof Throwable
                ? $sanitizer($error->getMessage())
                : (! $ok && is_string($error) ? $sanitizer($error) : null),
            'meta' => $sanitizer($result->meta()),
        ];
    }

    private static function defaultSanitizer(mixed $value): mixed
    {
        // Pull overrides from Laravel config if available; fall back to hardcoded defaults.
        $debugConfig = self::debugConfig();
        $enabled = ($debugConfig['enabled'] ?? true) === true;
        $redaction = $debugConfig['redaction'] ?? '***REDACTED***';
        $sensitiveKeys = $debugConfig['sensitive_keys'] ?? [
            'password',
            'pass',
            'secret',
            'token',
            'api_key',
            'apikey',
            'ssn',
            'card',
            'authorization',
        ];
        $max = is_int($debugConfig['max_string_length'] ?? null)
            ? $debugConfig['max_string_length']
            : 200;
        $truncateStrings = ($debugConfig['truncate_strings'] ?? true) === true;

        if (! $enabled) {
            return $value;
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $lowerKey = is_string($k) ? strtolower($k) : '';
                $isSensitive = false;
                foreach ($sensitiveKeys as $s) {
                    if ($s !== '' && str_contains($lowerKey, $s)) {
                        $isSensitive = true;
                        break;
                    }
                }
                if ($isSensitive) {
                    $out[$k] = $redaction;
                } else {
                    $out[$k] = self::defaultSanitizer($v);
                }
            }

            return $out;
        }

        if (is_string($value)) {
            // Truncate very long strings (tokens, dumps) to avoid leaking full contents.
            if ($truncateStrings && self::stringLength($value) > $max) {
                return self::stringSlice($value, 0, $max).'…';
            }

            return $value;
        }

        return $value;
    }

    /**
     * Fetch debug config from Laravel if the helper is available; otherwise return defaults.
     *
     * @return array{enabled?: bool, redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int, truncate_strings?: bool}
     */
    private static function debugConfig(): array
    {
        if (function_exists('config')) {
            /** @var array{redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int}|null $config */
            $config = config('result-flow.debug');

            if (is_array($config)) {
                return $config;
            }
        }

        return [];
    }

    private static function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private static function stringSlice(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, $start, $length) : substr($value, $start, $length);
    }
}
