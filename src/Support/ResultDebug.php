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
     * @return array{ok: bool, value_type: string|null, error_type: string|null, error_message: mixed, log_level: string|null, meta: mixed}
     */
    public static function toDebugArray(Result $result, ?callable $sanitizer = null): array
    {
        $sanitizer = $sanitizer ?? [self::class, 'defaultSanitizer'];
        $ok = $result->isOk();
        $error = $result->error();
        $logLevel = ! $ok ? self::resolveLogLevel($error) : null;

        return [
            'ok' => $ok,
            'value_type' => $ok ? get_debug_type($result->value()) : null,
            'error_type' => ! $ok ? get_debug_type($error) : null,
            'error_message' => ! $ok && $error instanceof Throwable
                ? $sanitizer($error->getMessage())
                : (! $ok && is_string($error) ? $sanitizer($error) : null),
            'log_level' => $logLevel,
            'meta' => $sanitizer($result->meta()),
        ];
    }

    private static function resolveLogLevel(mixed $error): ?string
    {
        $debugConfig = self::debugConfig();
        $map = is_array($debugConfig['log_level_map'] ?? null) ? $debugConfig['log_level_map'] : [];
        $default = $debugConfig['default_log_level'] ?? 'error';

        $level = self::findLogLevel($error, $map);

        if ($level !== null) {
            return $level;
        }

        return is_string($default) ? $default : null;
    }

    /**
     * @param  array<int|string,string>  $map
     */
    private static function findLogLevel(mixed $error, array $map): ?string
    {
        if ($error instanceof Throwable) {
            $level = self::matchThrowableLogLevel($error, $map);
            if ($level !== null) {
                return $level;
            }

            $code = $error->getCode();
            if (is_int($code) || is_string($code)) {
                $level = self::matchLogLevelKey($map, $code);
                if ($level !== null) {
                    return $level;
                }
            }
        }

        if (is_array($error) && array_key_exists('code', $error)) {
            $code = $error['code'];
            if (is_int($code) || is_string($code)) {
                $level = self::matchLogLevelKey($map, $code);
                if ($level !== null) {
                    return $level;
                }
            }
        }

        if (is_int($error) || is_string($error)) {
            return self::matchLogLevelKey($map, $error);
        }

        return null;
    }

    /**
     * @param  array<int|string,string>  $map
     */
    private static function matchThrowableLogLevel(Throwable $error, array $map): ?string
    {
        $parents = class_parents($error);
        if ($parents === false) {
            $parents = [];
        }
        $implements = class_implements($error);
        if ($implements === false) {
            $implements = [];
        }
        $classes = array_merge([$error::class], $parents, $implements);

        foreach ($classes as $name) {
            if (array_key_exists($name, $map) && is_string($map[$name])) {
                return $map[$name];
            }
        }

        return null;
    }

    /**
     * @param  array<int|string,string>  $map
     */
    private static function matchLogLevelKey(array $map, int|string $key): ?string
    {
        $keysToCheck = [$key];
        // Handle numeric string keys that may be cast to ints in PHP arrays.
        if (is_int($key)) {
            $keysToCheck[] = (string) $key;
        } elseif (is_string($key) && ctype_digit($key)) {
            $keysToCheck[] = (int) $key;
        }

        foreach ($keysToCheck as $lookupKey) {
            if (array_key_exists($lookupKey, $map) && is_string($map[$lookupKey])) {
                return $map[$lookupKey];
            }
        }

        return null;
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
     * @return array{enabled?: bool, redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int, truncate_strings?: bool, log_level_map?: array<int|string,string>, default_log_level?: string|null}
     */
    private static function debugConfig(): array
    {
        if (function_exists('config')) {
            /** @var array{redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int, truncate_strings?: bool, log_level_map?: array<int|string,string>, default_log_level?: string|null}|null $config */
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
