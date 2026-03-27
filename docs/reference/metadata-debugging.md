---
title: Metadata and Debugging Reference
---

# Metadata and Debugging Reference

```php
$debug = $result->toDebugArray();
```

## `meta(): array`

Returns the current metadata map.

## `tapMeta(callable $tap): Result`

Observes metadata without changing the result. On `Ok`, the callback will be invoked with the metadata as the first argument and the success value as an optional second argument; on `Fail` the callback receives metadata only.

Note: If the provided callable accepts two parameters the library will pass the value as the second argument for `Ok` and `null` for `Fail`. To be explicit and friendly to static analyzers prefer an optional/nullable second parameter, e.g. `fn(array $meta, $value = null)` or `fn(array $meta, ?MyType $value = null)`.

## `mapMeta(callable $map): Result`

Replaces metadata with the callback output.

The argument is a callable only.

On `Ok`, the callback receives the current metadata as the first argument and the current value as the second argument.

On `Fail`, the callback receives metadata only. When the callable accepts two arguments, the second argument will be `null` on `Fail`.

```php
$result = $result->mapMeta(fn (array $meta, $value = null) => [
    ...$meta,
    'operation' => 'normalize',
    'value_type' => get_debug_type($value),
]);
```

## `mergeMeta(array|callable $meta): Result`

Adds or overwrites metadata keys.

The argument may be either an array or a callable.

On `Ok`, the callable receives the current metadata as the first argument and the current value as the second argument.

On `Fail`, the callable receives metadata only. When the callable accepts two arguments, the second argument will be `null` on `Fail`.

```php
$result = $result->mergeMeta(fn (array $meta, $value = null) => [
    ...$meta,
    'operation' => 'normalize',
    'value_type' => get_debug_type($value),
]);
```

## `tap(callable $tap): Result`

Runs a side effect for both branches.

## `onSuccess(callable $tap): Result`

Runs a side effect only on success.

## `inspect(callable $tap): Result`

Alias of `onSuccess(...)`.

## `onFailure(callable $tap): Result`

Runs a side effect only on failure.

## `inspectError(callable $tap): Result`

Alias of `onFailure(...)`.

## `toArray(): array`

Returns the raw branch shape:

```php
['ok' => bool, 'value' => mixed, 'error' => mixed, 'meta' => array]
```

## `toDebugArray(?callable $sanitizer = null): array`

Returns debug-safe output.

- success includes `value_type`
- failure includes `error_type` and `error_message`
- metadata is sanitized
- built-in sanitization redacts sensitive keys and truncates long strings
- this is the right place to inspect `failed_step` when a chain step throws

## `toJson(int $options = 0): string`

Serializes the raw array shape to JSON with `JSON_THROW_ON_ERROR`.

## `toXml(string $rootElement = 'result'): string`

Serializes the raw array shape to XML.

Behavior:

- invalid characters in element names become underscores
- names that cannot start as XML elements are prefixed with `item_`
- names starting with `xml` are also prefixed with `item_`
- numeric keys become `item{n}`

## `toResponse(): mixed`

Returns a Laravel JSON response when the framework response factory exists.

Outside Laravel, returns:

```php
[
    'status' => 200|400,
    'headers' => ['Content-Type' => 'application/json'],
    'body' => '...json...',
]
```

## Related pages

- [Observability guide](/guides/observability)
- [Finalization boundaries](/concepts/finalization-boundaries)
