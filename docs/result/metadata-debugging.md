---
title: Metadata, taps, and debugging
---

# Metadata, taps, and debugging

Metadata is a first-class citizen in Result Flow. It rides along every chain, can be transformed independently, and feeds into sanitized debug output.

## Working with metadata

Metadata is a simple associative array carried on every `Result`. It is useful for correlation IDs, step names, audit context, or failed input.

### Inspect without mutating using `tapMeta()`

```php
Result::ok($payload, ['request_id' => $rid])
    ->tapMeta(fn (array $meta) => logger()->debug('request', $meta));
```

`tapMeta()` does not change the stored metadata. It receives a copy of the array.

### Replace metadata with `mapMeta()`

```php
$replaced = Result::ok($user, ['source' => 'import'])
    ->mapMeta(fn (array $meta) => ['source' => 'override', 'step' => 'mapped']);
```

`mapMeta()` returns a new `Result` with the replaced metadata (the value or error is preserved).

### Merge metadata with `mergeMeta()`

```php
$merged = Result::ok($user, ['trace' => $traceId])
    ->mergeMeta(['step' => 'validated']);
```

`mergeMeta()` is a shallow merge. Later keys overwrite earlier keys.

### Keep metadata in chained steps

When a chained step returns a `Result`, its metadata becomes the source of truth for subsequent steps.

```php
$withSteps = Result::ok($payload, ['step' => 'received'])
    ->then(fn ($value, $meta) => Result::ok($value, [...$meta, 'validated' => true]))
    ->then(fn ($value, $meta) => Result::ok($value, [...$meta, 'persisted' => true]));

$withSteps->meta();
// ['step' => 'received', 'validated' => true, 'persisted' => true]
```

If a step returns a raw value (not a Result), the current metadata is preserved unchanged.

## Observing values without changing state

Use taps to emit logs or metrics while keeping the current `Result` untouched.

### `tap()` observes both branches

```php
Result::ok($payload)
    ->tap(fn ($value, $error, $meta) => metrics()->increment('pipeline.start'));
```

### `onSuccess()` and `inspect()`

`inspect()` is an alias for `onSuccess()`.

```php
Result::ok($payload)
    ->onSuccess(fn ($value, $meta) => audit('ok', $meta))
    ->inspect(fn ($value, $meta) => logger()->info('ok', $meta));
```

### `onFailure()` and `inspectError()`

`inspectError()` is an alias for `onFailure()`.

```php
Result::fail('bad')
    ->onFailure(fn ($error, $meta) => logger()->warning('fail', ['error' => $error] + $meta))
    ->inspectError(fn ($error, $meta) => report($error));
```

## Debug output and sanitization

### `toArray()` for raw inspection

`toArray()` returns the exact stored value, error, and metadata. Use it for serialization where you control the output destination.

### `toDebugArray()` for safe logging

`toDebugArray()` hides sensitive data using a sanitizer. By default, it redacts common keys (password, token, authorization, etc.) and truncates long strings.

```php
$debug = Result::fail(new DomainException('Order 42 is invalid'), ['token' => 'secret'])
    ->toDebugArray();
```

Behavior:
- `value_type` and `error_type` are derived from `get_debug_type()`.
- `error_message` is populated only for string errors and Throwables.
- Metadata is sanitized recursively.

For full details on configuring redaction, wildcards, and string truncation, see the dedicated Sanitization and Safety guide.
