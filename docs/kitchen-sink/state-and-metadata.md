---
title: Branch State and Metadata
---

# Branch State and Metadata

This group covers branch inspection and metadata operations.

## Quick Map

| Function       | What it does                                  |
| -------------- | --------------------------------------------- |
| `isOk`         | Checks whether the result is successful       |
| `isFail`       | Checks whether the result is failed           |
| `value`        | Returns the success value                     |
| `error`        | Returns the failure value                     |
| `meta`         | Returns the current metadata                  |
| `tapMeta`      | Observes metadata without changing the result |
| `mapMeta`      | Replaces metadata                             |
| `mergeMeta`    | Merges new metadata into the current metadata |
| `tap`          | Observes both branches                        |
| `onSuccess`    | Observes only success                         |
| `inspect`      | Alias of `onSuccess`                          |
| `onFailure`    | Observes only failure                         |
| `inspectError` | Alias of `onFailure`                          |

## isOk

`isOk()` returns `true` when the current branch is success.

```php
isOk(): bool
```

Use it for explicit branching when you need a boolean check before reading value-specific data.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(42);

if ($result->isOk()) {
    // safe to read success-only data
}
```

## isFail

`isFail()` returns `true` when the current branch is failure.

```php
isFail(): bool
```

It is the inverse of `isOk()` and is useful when failure is the normal thing you need to handle.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::fail('Something went wrong');

if ($result->isFail()) {
    // inspect the failure path
}
```

## value

`value()` returns the success value when the branch is `Ok`, otherwise `null`.

```php
value(): mixed
```

It does not change the result, so it is safe to call when you only need to inspect the payload.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(42);

$value = $result->value();
```

## error

`error()` returns the failure value when the branch is `Fail`, otherwise `null`.

```php
error(): mixed
```

Use it when you need the rejected value for logging, matching, or custom recovery.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::fail('Something went wrong');

$error = $result->error();
```

## meta

`meta()` returns the current metadata array from either branch.

```php
meta(): array
```

It is a read-only view of the metadata that has been accumulated so far.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(42);

$meta = $result->meta();
```

## tapMeta

`tapMeta(...)` calls the callback with the current metadata and leaves the result unchanged. On `Ok`, the callback will be invoked with the metadata as the first argument and the success value as an optional second argument; on `Fail` the callback receives metadata only.

```php
tapMeta(callable $tap): self
```

### Inputs:

* `$tap`: callback that receives the metadata (and optionally the value when the result is `Ok`)

Use it for metadata observation, logging, or debugging.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(42);

$result = $result->tapMeta(fn (array $meta, $value = null) => logger()->debug('meta', compact('meta', 'value')));

Note: Callbacks that accept two parameters will now receive the current value for `Ok` results and `null` for `Fail` results. To be explicit and avoid static analyzer warnings, prefer an optional/nullable second parameter (e.g. `fn(array $meta, $value = null)` or `fn(array $meta, ?MyType $value = null)`).
```

## mapMeta

`mapMeta(...)` replaces metadata with the callback output.

```php
mapMeta(callable $map): self
```

### Inputs:

* `$map`: callback that receives the current metadata and, when the result is `Ok`, the current value as a second argument

### Behavior:

- on `Ok`, the callback receives the current metadata and the value as the second argument
- on `Fail`, the callback receives metadata only
- the result keeps the same branch and new metadata

Note: If the callback accepts two parameters, the library will pass the value as the second argument when the result is `Ok` and `null` when the result is `Fail`. Use an optional/nullable second parameter to handle both branches safely.

Use it when the metadata shape itself should change.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(42);

$result = $result->mapMeta(fn (array $meta, $value = null) => [
    ...$meta,
    'operation' => 'normalize',
    'value_type' => get_debug_type($value),
]);
```

## mergeMeta

`mergeMeta(...)` adds or overwrites metadata keys.

```php
mergeMeta(array|callable $meta): self
```

### Inputs:

* `$meta`: array or callback used to merge metadata

### Behavior:

- array input merges keys directly
- callable input may inspect metadata, and on `Ok` also receives the current value as a second argument

Note: As with `mapMeta`, when a callable is provided that accepts two parameters it will receive `(meta, value)` on `Ok` and `(meta, null)` on `Fail`. Prefer `fn(array $meta, $value = null)` or a nullable typed second parameter.

Use it when you want to add a few keys without replacing the whole map.

Use:

```php
$result = $result->mergeMeta(['operation' => 'normalize']);

$result = $result->mergeMeta(fn (array $meta, $value = null) => [
    ...$meta,
    'operation' => 'normalize',
    'value_type' => get_debug_type($value),
]);
```

## tap

`tap(...)` sees both branches in one callback.

```php
tap(callable $tap): self
```

### Inputs:

* `$tap`: callback that receives `(valueOrNull, errorOrNull, meta)`

It receives `(valueOrNull, errorOrNull, meta)` and returns the original result unchanged.

Use it when you need one observation point for both outcomes.

Use:

```php
$result = $result->tap(
    fn ($value, $error, array $meta) => logger()->debug('flow', compact('value', 'error', 'meta')),
);
```

## onSuccess

`onSuccess(...)` runs only when the result is successful.

```php
onSuccess(callable $tap): self
```

### Inputs:

* `$tap`: callback that receives the success value and metadata

The callback receives the success value and metadata, and the original result is returned unchanged.

Use them for success-only logging, metrics, or instrumentation.

Use:

```php
$result = $result->onSuccess(fn ($value, array $meta) => logger()->info('saved', $meta));
```

## inspect

`inspect(...)` is an alias of `onSuccess(...)`.

```php
inspect(callable $tap): self
```

Use it when you prefer the method name that reads like an observation hook.

Use:

```php
$result = $result->inspect(fn ($value, array $meta) => logger()->info('saved', $meta));
```

## onFailure

`onFailure(...)` runs only when the result is a failure.

```php
onFailure(callable $tap): self
```

### Inputs:

* `$tap`: callback that receives the failure value and metadata

The callback receives the failure value and metadata, and the original result is returned unchanged.

Use it for failure-only logging, metrics, or instrumentation.

Use:

```php
$result = $result->onFailure(fn ($error, array $meta) => logger()->warning('failed', $meta));
```

## inspectError

`inspectError(...)` is an alias of `onFailure(...)`.

```php
inspectError(callable $tap): self
```

Use it when you prefer the method name that reads like an error inspection hook.

Use:

```php
$result = $result->inspectError(fn ($error, array $meta) => logger()->warning('failed', $meta));
```

## See Also

- [Metadata debugging reference](/reference/metadata-debugging)
- [Observability guide](/guides/observability)
- [Kitchen sink overview](./)
