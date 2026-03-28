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

## `matchError(array $handlers, callable $onSuccess, callable $onUnhandled): mixed`

Matches structured domain errors by class.

- handlers are keyed by `DataTaggedError` / `ResultError` class name
- unmatched structured errors fall through to `$onUnhandled`
- string codes are not used for dispatch

```php
$message = $result->matchError(
    [UserPersistError::class => fn (UserPersistError $e) => $e->code()],
    onSuccess: fn ($value) => 'ok',
    onUnhandled: fn ($error) => 'unhandled',
);
```

## `catchError(array $handlers, ?callable $fallback = null): Result`

Handles structured domain errors by class and keeps the flow inside `Result`.

- handlers are keyed by `DataTaggedError` / `ResultError` class name
- handlers may return a plain value or a `Result`
- unmatched failures return unchanged when no fallback is provided
- fallback also handles legacy non-`ResultError` failures

```php
$result = $result->catchError([
    UserPersistError::class => fn (UserPersistError $e) => 'retry-later',
]);
```

## `recover(callable $fn): Result`

Converts any failure into a success result.

## Related pages

- [Failure handling concepts](/concepts/failure-handling)
- [Finalization boundaries](/concepts/finalization-boundaries)
