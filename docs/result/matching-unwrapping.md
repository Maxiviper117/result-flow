---
title: Matching and Unwrapping
---

# Matching and Unwrapping

## What this page is for

Use this page to finish a pipeline and extract a value, response, or exception.

## `match()`

Handle both branches explicitly.

```php
$output = $result->match(
    onSuccess: fn ($value, array $meta) => ['ok' => true, 'data' => $value, 'meta' => $meta],
    onFailure: fn ($error, array $meta) => ['ok' => false, 'error' => (string) $error, 'meta' => $meta],
);
```

## `matchException()`

Match Throwable failures by class, with explicit handlers.

```php
$message = $result->matchException(
    exceptionHandlers: [
        RuntimeException::class => fn (RuntimeException $e) => "runtime: {$e->getMessage()}",
    ],
    onSuccess: fn ($value) => 'ok',
    onUnhandled: fn ($error) => "unhandled: {$error}",
);
```

## Unwrap family

- `unwrap()`: returns success value; throws on failure.
- `unwrapOr($default)`: returns default on failure.
- `unwrapOrElse(fn($error, $meta))`: lazily computes default on failure.
- `getOrThrow(fn($error, $meta) => Throwable)`: custom throw strategy.

```php
$value = $result->unwrapOrElse(fn ($error) => "fallback: {$error}");
```

## Choosing unwrap method

| Need | Use |
|---|---|
| Throw on failure | `unwrap` |
| Cheap static default | `unwrapOr` |
| Expensive/lazy default | `unwrapOrElse` |
| Custom exception class | `getOrThrow` |

## Related pages

- [Error Handling](/result/error-handling)
- [Transformers](/result/transformers)
- [API Reference](/api)
