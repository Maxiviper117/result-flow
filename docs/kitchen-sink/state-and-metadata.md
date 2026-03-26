---
title: Branch State and Metadata
---

# Branch State and Metadata

This group covers branch inspection and metadata operations.

## Quick Map

| Function | What it does |
| --- | --- |
| `isOk` | Checks whether the result is successful |
| `isFail` | Checks whether the result is failed |
| `value` | Returns the success value |
| `error` | Returns the failure value |
| `meta` | Returns the current metadata |
| `tapMeta` | Observes metadata without changing the result |
| `mapMeta` | Replaces metadata |
| `mergeMeta` | Merges new metadata into the current metadata |
| `tap` | Observes both branches |
| `onSuccess` | Observes only success |
| `inspect` | Alias of `onSuccess` |
| `onFailure` | Observes only failure |
| `inspectError` | Alias of `onFailure` |

## isOk

`isOk()` returns `true` when the current branch is success.

Use it for explicit branching when you need a boolean check before reading value-specific data.

Shape:

```php
// true
// or false
```

Use:

```php
if ($result->isOk()) {
    // safe to read success-only behavior
}
```

## isFail

`isFail()` returns `true` when the current branch is failure.

It is the inverse of `isOk()` and is useful when failure is the normal thing you need to handle.

Shape:

```php
// true
// or false
```

Use:

```php
if ($result->isFail()) {
    // inspect the failure path
}
```

Use either method when you want branch detection without transforming the result.

## value

`value()` returns the success value when the branch is `Ok`, otherwise `null`.

It does not change the result, so it is safe to call when you only need to inspect the payload.

Shape:

```php
// $successValue
// or null
```

Use:

```php
$value = $result->value();
```

## error

`error()` returns the failure value when the branch is `Fail`, otherwise `null`.

Use it when you need the rejected value for logging, matching, or custom recovery.

Shape:

```php
// $failureValue
// or null
```

Use:

```php
$error = $result->error();
```

## meta

`meta()` returns the current metadata array from either branch.

It is a read-only view of the metadata that has been accumulated so far.

Shape:

```php
// ['request_id' => 'r-1', 'step' => 'validate']
```

Use:

```php
$meta = $result->meta();
```

Use these read methods when a later step or boundary needs direct access to the branch data.

## tapMeta

`tapMeta(...)` calls the callback with the current metadata and leaves the result unchanged.

Use it for metadata-only observation, logging, or debugging.

Shape:

```php
// returns the same Result instance
```

Use:

```php
$result = $result->tapMeta(fn (array $meta) => logger()->debug('meta', $meta));
```

## mapMeta

`mapMeta(...)` replaces metadata with the callback output.

- on `Ok`, the callback receives the current value and metadata
- on `Fail`, the callback receives metadata only
- the argument is a callable

Use it when the metadata shape itself should change.

Shape:

```php
// Ok($value, meta: [...$newMeta])
// or Fail($error, meta: [...$newMeta])
```

Use:

```php
$result = $result->mapMeta(fn ($value, array $meta) => [
    ...$meta,
    'operation' => 'normalize',
    'value_type' => get_debug_type($value),
]);
```

## mergeMeta

`mergeMeta(...)` adds or overwrites metadata keys.

- the argument may be an array or a callable
- array input merges keys directly
- callable input may inspect metadata, and on `Ok` also receives the current value

Use it when you want to add a few keys without replacing the whole map.

Shape:

```php
// Ok($value, meta: [...$mergedMeta])
// or Fail($error, meta: [...$mergedMeta])
```

Use:

```php
$result = $result->mergeMeta(['operation' => 'normalize']);
```

Use a callable when the merged keys depend on the current metadata or success value:

```php
$result = $result->mergeMeta(fn ($value, array $meta) => [
    ...$meta,
    'operation' => 'normalize',
    'value_type' => get_debug_type($value),
]);
```

## tap

`tap(...)` sees both branches in one callback.

It receives `(valueOrNull, errorOrNull, meta)` and returns the original result unchanged.

Use it when you need one observation point for both outcomes.

Shape:

```php
// returns the same Result instance
```

Use:

```php
$result = $result->tap(
    fn ($value, $error, array $meta) => logger()->debug('flow', compact('value', 'error', 'meta')),
);
```

## onSuccess

`onSuccess(...)` runs only when the result is successful.

The callback receives the success value and metadata, and the original result is returned unchanged.

Use them for success-only logging, metrics, or instrumentation.

Shape:

```php
// returns the same Result instance
```

Use:

```php
$result = $result->onSuccess(fn ($value, array $meta) => logger()->info('saved', $meta));
```

## inspect

`inspect(...)` is an alias of `onSuccess(...)`.

Use it when you prefer the method name that reads like an observation hook.

Shape:

```php
// returns the same Result instance
```

Use:

```php
$result = $result->inspect(fn ($value, array $meta) => logger()->info('saved', $meta));
```

## onFailure

`onFailure(...)` runs only when the result is failed.

The callback receives the failure value and metadata, and the original result is returned unchanged.

Use them for failure-only logging, metrics, or instrumentation.

Shape:

```php
// returns the same Result instance
```

Use:

```php
$result = $result->onFailure(fn ($error, array $meta) => logger()->warning('failed', $meta));
```

## inspectError

`inspectError(...)` is an alias of `onFailure(...)`.

Use it when you want the observation-style name for the failure branch.

Shape:

```php
// returns the same Result instance
```

Use:

```php
$result = $result->inspectError(fn ($error, array $meta) => logger()->warning('failed', $meta));
```

## See Also

- [Metadata reference](/reference/metadata-debugging)
- [Observability guide](/guides/observability)
- [Kitchen sink overview](./)
