---
title: Metadata and Debugging
---

# Metadata and Debugging

## What this page is for

Use this page to manage metadata flow, add observability hooks, and generate debug-safe output.

## Metadata operations

### `meta()`

Read current metadata.

### `tapMeta(fn)`

Read metadata for side effects without changing result.

### `mapMeta(fn)`

Return a transformed metadata array.

### `mergeMeta(array)`

Merge new metadata keys into current metadata.

```php
$result = Result::ok($dto, ['request_id' => 'r-1'])
    ->mergeMeta(['step' => 'validated'])
    ->mapMeta(fn (array $meta) => [...$meta, 'version' => 2]);
```

## Tap methods

- `tap(value,error,meta)` for both branches.
- `onSuccess` / `inspect` for success only.
- `onFailure` / `inspectError` for failure only.

These are ideal for logging/metrics and do not alter payloads.

## Debug-safe output

### `toDebugArray(?sanitizer)`

Use for logs and diagnostics where sensitive values must be redacted.

```php
$debug = $result->toDebugArray();
```

### `toArray()`

Raw shape, no sanitization.

Use `toArray()` for trusted internal serialization only.

## Related pages

- [Sanitization Guide](/sanitization)
- [Testing Recipes](/testing)
- [API Reference](/api)
