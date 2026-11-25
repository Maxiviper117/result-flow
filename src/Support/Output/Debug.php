<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Output;

use JsonSerializable;
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\Cause;
use Maxiviper117\ResultFlow\Support\Errors\ResultError;
use Throwable;

/**
 * Debug-friendly serialization with optional sanitization.
 *
 * @internal
 */
final class Debug
{
    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(mixed): mixed|null  $sanitizer
     * @return array{ok: bool, value_type: string|null, error_type: string|null, error_code: string|null, error_message: mixed, error_cause: mixed, meta: mixed}
     */
    public static function toDebugArray(Result $result, ?callable $sanitizer = null): array
    {
        $sanitizer = $sanitizer ?? [self::class, 'defaultSanitizer'];
        $ok = $result->isOk();
        $error = $result->match(fn ($value) => null, fn ($error) => $error);

        $errorCode = null;
        $errorMessage = null;
        $errorCause = null;

        if (! $ok) {
            if ($error instanceof ResultError) {
                $errorCode = $error->code();
                $errorMessage = $sanitizer($error->message());
                $cause = $error->cause();
                if ($cause instanceof Cause) {
                    $errorCause = $sanitizer($cause->toArray());
                }
            } elseif ($error instanceof Throwable) {
                $errorMessage = $sanitizer($error->getMessage());
            } elseif (is_string($error)) {
                $errorMessage = $sanitizer($error);
            }
        }

        return [
            'ok' => $ok,
            'value_type' => $ok ? $result->match(fn ($value) => get_debug_type($value), fn ($error) => null) : null,
            'error_type' => ! $ok ? get_debug_type($error) : null,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'error_cause' => $errorCause,
            'meta' => $sanitizer($result->meta()),
        ];
    }

    /**
     * Default sanitizer that redacts sensitive keys and truncates long strings.
     */
    private static function defaultSanitizer(mixed $value): mixed
    {
        /** @var array{enabled?: bool, redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int, truncate_strings?: bool, sanitize_objects?: bool, object_max_depth?: int, array_max_depth?: int} $debugConfig */
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
        $sanitizeObjects = ($debugConfig['sanitize_objects'] ?? false) === true;
        $maxDepth = is_int($debugConfig['object_max_depth'] ?? null)
            ? max(1, $debugConfig['object_max_depth'])
            : 3;
        $arrayMaxDepth = is_int($debugConfig['array_max_depth'] ?? null)
            ? min(128, max(1, $debugConfig['array_max_depth']))
            : 32;

        if (! $enabled) {
            return $value;
        }

        /** @var array<int, bool> $seen */
        $seen = [];
        /** @var array<string, bool> $seenArrayRefs */
        $seenArrayRefs = [];

        return self::sanitizeValue(
            $value,
            redaction: $redaction,
            sensitiveKeys: $sensitiveKeys,
            truncateStrings: $truncateStrings,
            maxStringLength: $max,
            sanitizeObjects: $sanitizeObjects,
            maxDepth: $maxDepth,
            depth: 0,
            arrayMaxDepth: $arrayMaxDepth,
            arrayDepth: 0,
            seen: $seen,
            seenArrayRefs: $seenArrayRefs,
        );
    }

    /**
     * @param  array<int, string>  $sensitiveKeys
     * @param  array<int, bool>  $seen
     * @param  array<string, bool>  $seenArrayRefs
     */
    private static function sanitizeValue(
        mixed $value,
        string $redaction,
        array $sensitiveKeys,
        bool $truncateStrings,
        int $maxStringLength,
        bool $sanitizeObjects,
        int $maxDepth,
        int $depth,
        int $arrayMaxDepth,
        int $arrayDepth,
        array &$seen,
        array &$seenArrayRefs,
    ): mixed {
        if (is_array($value)) {
            if ($arrayDepth >= $arrayMaxDepth) {
                return '[array_depth_exceeded]';
            }
            $out = [];
            foreach ($value as $k => $v) {
                if (is_string($k) && self::matchesSensitiveKey($k, $sensitiveKeys)) {
                    $out[$k] = $redaction;
                } else {
                    $reference = is_array($v) ? \ReflectionReference::fromArrayElement($value, $k) : null;
                    $referenceId = $reference instanceof \ReflectionReference ? bin2hex($reference->getId()) : null;
                    if ($referenceId !== null && isset($seenArrayRefs[$referenceId])) {
                        $out[$k] = '[circular_reference]';

                        continue;
                    }
                    if ($referenceId !== null) {
                        $seenArrayRefs[$referenceId] = true;
                    }
                    $out[$k] = self::sanitizeValue(
                        $v,
                        redaction: $redaction,
                        sensitiveKeys: $sensitiveKeys,
                        truncateStrings: $truncateStrings,
                        maxStringLength: $maxStringLength,
                        sanitizeObjects: $sanitizeObjects,
                        maxDepth: $maxDepth,
                        depth: $depth + 1,
                        arrayMaxDepth: $arrayMaxDepth,
                        arrayDepth: $arrayDepth + 1,
                        seen: $seen,
                        seenArrayRefs: $seenArrayRefs,
                    );
                    if ($referenceId !== null) {
                        unset($seenArrayRefs[$referenceId]);
                    }
                }
            }

            return $out;
        }

        if (is_object($value) && $sanitizeObjects) {
            if ($depth >= $maxDepth) {
                return '[object_depth_exceeded]';
            }

            $objectId = spl_object_id($value);
            if (isset($seen[$objectId])) {
                return '[circular_reference]';
            }

            $seen[$objectId] = true;

            if ($value instanceof JsonSerializable) {
                $sanitized = self::sanitizeValue(
                    $value->jsonSerialize(),
                    redaction: $redaction,
                    sensitiveKeys: $sensitiveKeys,
                    truncateStrings: $truncateStrings,
                    maxStringLength: $maxStringLength,
                    sanitizeObjects: $sanitizeObjects,
                    maxDepth: $maxDepth,
                    depth: $depth + 1,
                    arrayMaxDepth: $arrayMaxDepth,
                    arrayDepth: $arrayDepth,
                    seen: $seen,
                    seenArrayRefs: $seenArrayRefs,
                );

                unset($seen[$objectId]);

                return $sanitized;
            }

            $props = get_object_vars($value);
            if ($props !== []) {
                $out = [];

                foreach ($props as $k => $v) {
                    if (self::matchesSensitiveKey($k, $sensitiveKeys)) {
                        $out[$k] = $redaction;
                    } else {
                        $out[$k] = self::sanitizeValue(
                            $v,
                            redaction: $redaction,
                            sensitiveKeys: $sensitiveKeys,
                            truncateStrings: $truncateStrings,
                            maxStringLength: $maxStringLength,
                            sanitizeObjects: $sanitizeObjects,
                            maxDepth: $maxDepth,
                            depth: $depth + 1,
                            arrayMaxDepth: $arrayMaxDepth,
                            arrayDepth: $arrayDepth,
                            seen: $seen,
                            seenArrayRefs: $seenArrayRefs,
                        );
                    }
                }

                unset($seen[$objectId]);

                return $out;
            }

            unset($seen[$objectId]);
        }

        if (is_string($value)) {
            if ($truncateStrings && self::stringLength($value) > $maxStringLength) {
                return self::stringSlice($value, 0, $maxStringLength).'…';
            }

            return $value;
        }

        return $value;
    }

    /**
     * Fetch debug config from Laravel if the helper is available; otherwise return defaults.
     *
     * @return array{enabled?: bool, redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int, truncate_strings?: bool, sanitize_objects?: bool, object_max_depth?: int, array_max_depth?: int}
     */
    private static function debugConfig(): array
    {
        if (function_exists('config')) {
            /** @var array{enabled?: bool, redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int, truncate_strings?: bool, sanitize_objects?: bool, object_max_depth?: int, array_max_depth?: int}|null $config */
            $config = config('result-flow.debug');

            if (is_array($config)) {
                /** @var array{enabled?: bool, redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int, truncate_strings?: bool, sanitize_objects?: bool, object_max_depth?: int, array_max_depth?: int} $config */
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
