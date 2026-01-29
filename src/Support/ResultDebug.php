<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support;

use Maxiviper117\ResultFlow\Result;
use Throwable;

/**
 * Debug-friendly serialization with optional sanitization.
 *
 * @internal
 */
final class ResultDebug
{
    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
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

    /**
     * Default sanitizer that redacts sensitive keys and truncates long strings.
     */
    private static function defaultSanitizer(mixed $value): mixed
    {
        // Pull overrides from Laravel config if available; fall back to hardcoded defaults.
        /** @var array{enabled?: bool, redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int, truncate_strings?: bool} $debugConfig */
        $debugConfig = self::debugConfig();
        $enabled = ($debugConfig['enabled'] ?? true) === true;
        $redaction = $debugConfig['redaction'] ?? '***REDACTED***';
        $defaultSensitiveKeys = [
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
        $rawSensitiveKeys = $debugConfig['sensitive_keys'] ?? null;
        /** @var array<int, mixed> $sensitiveKeys */
        $sensitiveKeys = is_array($rawSensitiveKeys) ? $rawSensitiveKeys : $defaultSensitiveKeys;
        $sensitiveKeys = array_values(array_filter(
            $sensitiveKeys,
            static fn ($value): bool => is_string($value) && $value !== ''
        ));
        /** @var array<int, string> $sensitiveKeys */
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
                // Only string keys are considered for sensitive matching.
                if (is_string($k) && self::matchesSensitiveKey($k, $sensitiveKeys)) {
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
            /** @var array{enabled?: bool, redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int, truncate_strings?: bool}|null $config */
            $config = config('result-flow.debug');

            if (is_array($config)) {
                /** @var array{enabled?: bool, redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int, truncate_strings?: bool} $config */
                return $config;
            }
        }

        return [];
    }

    /**
     * Determine whether a key matches any sensitive key pattern.
     *
     * @param  array<int, string>  $patterns
     */
    private static function matchesSensitiveKey(string $key, array $patterns): bool
    {
        // Cache compiled regexes per pattern list to avoid repeated compilation.
        /** @var array<string, array<int, string>> $cache */
        static $cache = [];

        if ($key === '') {
            return false;
        }

        $cacheKey = sha1(serialize($patterns));

        if (! isset($cache[$cacheKey])) {
            /** @var array<int, string> $regexes */
            $regexes = [];
            foreach ($patterns as $p) {
                if ($p === '') {
                    continue;
                }
                $hasGlob = strpbrk($p, '*?') !== false;
                $pattern = $hasGlob ? $p : '*'.$p.'*';
                $escaped = preg_quote($pattern, '/');
                $regex = '/^'.str_replace(['\\*', '\\?'], ['.*', '.'], $escaped).'$/i';
                $regexes[] = $regex;
            }
            $cache[$cacheKey] = $regexes;
        }

        foreach ($cache[$cacheKey] as $regex) {
            if ($regex === '') {
                continue;
            }
            if (preg_match($regex, $key) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate string length with multibyte support when available.
     */
    private static function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    /**
     * Slice strings with multibyte support when available.
     */
    private static function stringSlice(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, $start, $length) : substr($value, $start, $length);
    }
}
