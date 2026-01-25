---
title: Debugging & Metadata
---

# Debugging & Metadata

## Metadata Flow

- Metadata is an array that travels with the `Result`.
- `then()` and `otherwise()` propagate updated metadata when a step returns a `Result` with new meta.
- `mergeMeta()` shallow merges; `mapMeta()` replaces; `tapMeta()` observes without mutation.
- `failWithValue($error, $failedValue, $meta = [])` stores the failed input at `meta['failed_value']` for later logging.

## Debug-Safe Output

`toDebugArray(?callable $sanitizer = null)` produces a sanitized structure:

```php
[
  'ok' => bool,
  'value_type' => string|null,
  'error_type' => string|null,
  'error_message' => string|null, // Throwable message or string error
  'log_level' => string|null,     // Resolved log level for the error
  'meta' => [...],                // sanitized recursively
]
```

Behavior:

- Sensitive keys (password, token, api_key, ssn, card, etc.) are redacted to `***REDACTED***`.
- Long strings are truncated (`max_string_length`, default 200) when `truncate_strings` is true.
- When Laravel's `config()` helper is available, settings are read from `config('result-flow.debug')`. Otherwise defaults are used.
- Log levels can be derived from a mapping of exception classes or error codes (`log_level_map`) with a `default_log_level` fallback.

Pass a custom sanitizer to override the defaults:

```php
$result->toDebugArray(function ($value) {
    // redact anything under "secret" keys and leave everything else unchanged
    return is_array($value) && array_key_exists('secret', $value)
        ? ['secret' => '***']
        : $value;
});
```

### Log level mapping

Use `log_level_map` to associate error classes/codes with log levels for observability:

```php
$config = [
    'log_level_map' => [
        RuntimeException::class => 'critical',
        404 => 'notice',
        'E_TIMEOUT' => 'warning',
    ],
    'default_log_level' => 'error',
];

$result = Result::fail(new RuntimeException('Boom'));
$result->toDebugArray()['log_level']; // "critical"
```

## Observability Hooks

Use side-effect helpers to log without mutating the pipeline:

```php
Result::ok($payload, ['request_id' => $rid])
    ->tap(fn($value, $error, $meta) => Log::info('result', compact('value', 'error', 'meta')))
    ->onSuccess(fn($value, $meta) => Metrics::increment('result.ok'))
    ->onFailure(fn($error, $meta) => Log::warning('result.fail', ['error' => $error] + $meta));
```
