---
title: Metadata, taps, and debugging
---

# Metadata, taps, and debugging

Metadata is a first-class citizen in Result Flow. It rides along every chain, can be transformed independently, and feeds into sanitized debug output.

## Working with metadata

### Inspect without mutating using `tapMeta()`

```php
Result::ok($payload, ['request_id' => $rid])
    ->tapMeta(fn(array $meta) => logger()->debug('request', $meta));
```

### Replace or merge metadata

```php
// Replace the metadata entirely
$replaced = Result::ok($user)
    ->mapMeta(fn(array $meta) => ['source' => 'import']);

// Merge without losing existing keys
$merged = $replaced->mergeMeta(['trace' => $traceId]);
```

### Keep metadata in chained steps

When a chained step returns a `Result`, any metadata on the returned object becomes the source of truth for subsequent steps.

```php
$withSteps = Result::ok($payload, ['step' => 'received'])
    ->then(fn($value, $meta) => Result::ok($value, [...$meta, 'validated' => true]))
    ->then(fn($value, $meta) => Result::ok($value, [...$meta, 'persisted' => true]));

$withSteps->meta();
// ['step' => 'received', 'validated' => true, 'persisted' => true]
```

## Observing values without changing state

Use taps to emit logs or metrics while keeping the current `Result` untouched.

```php
$result = Result::ok($payload)
    ->tap(fn($value, $error, $meta) => metrics()->increment('pipeline.start'))
    ->onSuccess(fn($value, $meta) => audit('ok', $meta))
    ->onFailure(fn($error, $meta) => audit('failed', ['error' => $error] + $meta));
```

## Debug output and sanitization

### `toArray()` for raw inspection

`toArray()` returns the exact stored value, error, and metadata. Use it for serialization where you control the output destination.

### `toDebugArray()` for safe logging

`toDebugArray()` hides sensitive data using a sanitizer. By default, it redacts common keys (`password`, `token`, `authorization`, etc.) and truncates long strings. The `sensitive_keys` configuration supports glob-style patterns using `*` (matches any sequence) and `?` (matches any single character); plain words are treated as substring matches for backward compatibility. You can override the sanitizer or configure defaults via Laravel's `config('result-flow.debug')` if available.

```php
$config = [
    'enabled' => true,
    'redaction' => '***',
    'sensitive_keys' => ['token', 'secret'],
    'max_string_length' => 64,
    'truncate_strings' => true,
];

$debug = Result::ok(['token' => 'super-long-secret'], ['request_id' => $rid])
    ->toDebugArray();

// Produces something like:
// [
//   'ok' => true,
//   'value_type' => 'array',
//   'error_type' => null,
//   'error_message' => null,
//   'meta' => ['request_id' => $rid],
// ]
```

If you want to show parts of the error message but still sanitize it, pass your own sanitizer function:

```php
$debuggable = Result::fail(new DomainException('Order 42 is invalid'))
    ->toDebugArray(fn($value) => is_string($value) ? substr($value, 0, 10) : $value);
```
