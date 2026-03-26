---
title: Failure Handling Reference
---

# Failure Handling Reference

```php
$result = Result::fail('timeout')
    ->otherwise(fn ($error, array $meta) => Result::fail(['code' => 'TIMEOUT', 'message' => $error], $meta));
```

## `otherwise(callable|object|array $next): Result`

Runs only on failure.

- plain return value -> recovery to success
- returned `Result` -> kept as returned
- success branch -> passed through unchanged

## `catchException(array $handlers, ?callable $fallback = null): Result`

Matches `Throwable` failures by class.

- unmatched Throwable failure -> original result if no fallback is provided
- non-Throwable failure -> original result if no fallback is provided
- handlers and fallback may return a plain value or a `Result`

## `recover(callable $fn): Result`

Converts any failure into a success result.

## Related pages

- [Failure handling concepts](/concepts/failure-handling)
- [Finalization boundaries](/concepts/finalization-boundaries)
