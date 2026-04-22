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
- use this early to normalize `TFailure|Throwable` into one stable domain shape

## `matchError(array $errorHandlers, callable $onSuccess, callable $onUnhandled): mixed`

Matches structured domain errors by class.

- handlers are keyed by `ResultError` class name (including `DataTaggedError` subclasses)
- handlers are checked in array iteration order; the first matching class wins
- matched handlers may accept either `(TError)` or `(TError, array $meta)`
- `onSuccess` and `onUnhandled` may accept `()`, `(valueOrError)`, or `(valueOrError, array $meta)`
- if the failure is not a `ResultError`, or no class matches, `$onUnhandled` runs
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

- handlers are keyed by `ResultError` class name (including `DataTaggedError` subclasses)
- handlers are checked in array iteration order; the first matching class wins
- handlers and fallback may accept `()`, `(error)`, or `(error, array $meta)`
- each callback may return either a plain recovered success value or a `Result`
- if the failure is not a `ResultError`, class handlers are skipped and fallback is used when provided
- unmatched failures return unchanged when no fallback is provided
- fallback can handle both unmatched structured errors and legacy non-`ResultError` failures

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
- [Error normalization guide](/guides/error-normalization)
