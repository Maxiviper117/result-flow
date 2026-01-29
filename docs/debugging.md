---
title: Debugging & Metadata
---

# Debugging & Metadata

This page summarizes how metadata flows through a `Result` and how to produce debug-safe output. For detailed examples of taps and metadata helpers, see the Metadata, taps, and debugging guide in the Result deep dive.

## Metadata flow

- Metadata is an array that travels with the `Result`.
- `then()` and `otherwise()` propagate updated metadata when a step returns a `Result` with new meta.
- `mergeMeta()` shallow merges; `mapMeta()` replaces; `tapMeta()` observes without mutation.
- `failWithValue($error, $failedValue, $meta = [])` stores the failed input at `meta['failed_value']`.

## Debug-safe output

`toDebugArray(?callable $sanitizer = null)` produces a sanitized structure:

```php
[
  'ok' => bool,
  'value_type' => string|null,
  'error_type' => string|null,
  'error_message' => string|null, // Throwable message or string error
  'meta' => [...],                // sanitized recursively
]
```

Behavior:
- Sensitive keys (password, token, api_key, ssn, card, etc.) are redacted to `***REDACTED***`.
- Long strings are truncated (`max_string_length`, default 200) when `truncate_strings` is true.
- `error_message` is populated only for Throwables and string errors.
- When Laravel's `config()` helper is available, settings are read from `config('result-flow.debug')`.

For detailed configuration, see the Sanitization and Safety guide.

## Observability hooks

Use side-effect helpers to log without mutating the pipeline:

```php
Result::ok($payload, ['request_id' => $rid])
    ->tap(fn ($value, $error, $meta) => Log::info('result', compact('value', 'error', 'meta')))
    ->onSuccess(fn ($value, $meta) => Metrics::increment('result.ok'))
    ->onFailure(fn ($error, $meta) => Log::warning('result.fail', ['error' => $error] + $meta));
```
