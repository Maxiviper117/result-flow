---
title: Retrying
---

# Retrying

## What this page is for

Use this page when operations can fail transiently and you want controlled retries.

## `Result::retry()`

Simple entry point for retrying an operation.

```php
$result = Result::retry(
    times: 3,
    fn: fn () => callExternalApi(),
    delay: 100,
    exponential: true,
);
```

Behavior:
- Attempts up to `times`.
- Accepts callback returning plain value or `Result`.
- Returns final `Result` success/failure after retry policy completes.

## `Result::retrier()`

Advanced fluent builder.

```php
$result = Result::retrier()
    ->maxAttempts(5)
    ->delay(150)
    ->exponential()
    ->jitter(50)
    ->attachAttemptMeta()
    ->attempt(fn () => callExternalApi());
```

Typical options:
- max attempts
- base delay
- exponential backoff
- jitter
- retry predicate (`when(...)`)
- retry hooks (`onRetry(...)`)

## Retry metadata

When enabled, retrier can attach attempt count metadata:

```php
$attempts = $result->meta()['retry']['attempts'] ?? null;
```

## Related pages

- [Error Handling](/result/error-handling)
- [Metadata and Debugging](/result/metadata-debugging)
- [API Reference](/api)
