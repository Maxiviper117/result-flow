---
title: Boundaries Reference
---

# Boundaries Reference

```php
$payload = $result->match(
    onSuccess: fn ($value, array $meta) => ['ok' => true, 'data' => $value],
    onFailure: fn ($error, array $meta) => ['ok' => false, 'error' => $error],
);
```

## `match(callable $onSuccess, callable $onFailure): mixed`

Finishes the result by handling both branches explicitly.

## `matchException(array $exceptionHandlers, callable $onSuccess, callable $onUnhandled): mixed`

Handles Throwable failures by class, otherwise falls back to the unhandled callback.

## `unwrap(): mixed`

Returns the success value or throws the failure.

- if the failure is a `Throwable`, it is thrown directly
- otherwise a `RuntimeException` is thrown

## `unwrapOr(mixed $default): mixed`

Returns the success value or the eager default.

## `unwrapOrElse(callable $fn): mixed`

Returns the success value or computes a lazy default from the failure and metadata.

## `getOrThrow(callable $exceptionFactory): mixed`

Throws a custom exception produced from the failure and metadata.

## `throwIfFail(): Result`

Returns the same result on success. Throws on failure.

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

- [Finalization boundaries concept](/concepts/finalization-boundaries)
- [Failure handling reference](/reference/failure-handling)
