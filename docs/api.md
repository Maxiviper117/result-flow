---
title: API Reference
---

# API Reference

All methods live on `Maxiviper117\ResultFlow\Result`. Generic template annotations (PHPStan/Psalm) keep success/error types inferred through chains.

## Static Constructors

- `Result::ok(mixed $value, array $meta = []): Result<T, never>` — success.
- `Result::fail(mixed $error, array $meta = []): Result<never, E>` — failure.
- `Result::failWithValue(mixed $error, mixed $failedValue, array $meta = []): Result` — failure + `meta['failed_value']`.
- `Result::of(callable $fn): Result<T, Throwable>` — wrap callable, exceptions become `fail(Throwable)`.
- `Result::combine(array $results): Result<array<T>, E>` — fail-fast; merges meta.
- `Result::combineAll(array $results): Result<array<T>, array<E>>` — collects all errors.

## State & Access

- `isOk(): bool` / `isFail(): bool`
- `value(): mixed` — success payload or `null`.
- `error(): mixed` — error payload or `null`.
- `meta(): array<string,mixed>` — metadata.
- `toArray(): array{ok, value, error, meta}` — raw representation.
- `toDebugArray(?callable $sanitizer = null): array{ok, value_type, error_type, error_message, meta}` — safe, sanitized representation.

## Transformations

- `map(callable $fn): Result<U, E>` — transform success value; failure passes through.
- `mapError(callable $fn): Result<T, F>` — transform error; success passes through.
- `ensure(callable $predicate, mixed $error): Result<T, E>` — fail if predicate is falsey (uses PHP truthiness).

## Chaining (Success Path)

- `then(callable|object|array $next): Result<U, E>` — run on success; wraps exceptions as failure. Accepts closures, `__invoke`, `handle`, `execute`, or arrays of steps.
- `flatMap(callable $fn): Result<U, E>` — alias for `then()`.
- `thenUnsafe(callable|object $next): Result<U, E>` — run on success without try/catch; exceptions bubble.

## Chaining (Failure Path)

- `otherwise(callable|object|array $next): Result<T, F>` — run on failure. Returning a plain value recovers to success; returning `Result::fail()` keeps failing.
- `catchException(array $handlers, ?callable $fallback = null): Result<T, F>` — handle Throwable subclasses. First matching class wins; fallback handles non-Throwable or unmatched errors.
- `recover(callable $fn): Result<T|U, never>` — unconditionally convert failure to success.

## Side Effects (Do Not Alter State)

- `tap(callable $fn): Result` — observe both branches `(value, error, meta)`.
- `onSuccess(callable $fn): Result` / `inspect(callable $fn): Result` — observe success.
- `onFailure(callable $fn): Result` / `inspectError(callable $fn): Result` — observe failure.
- `tapMeta(callable $fn): Result` — observe metadata only.

## Metadata Helpers

- `mapMeta(callable $fn): Result` — replace metadata.
- `mergeMeta(array $meta): Result` — shallow merge metadata.

## Pattern Matching & Unwrapping

- `match(callable $onSuccess, callable $onFailure): mixed` — exhaustive handling.
- `matchException(array $handlers, callable $onSuccess, callable $onUnhandled): mixed` — dispatch on Throwable classes when failing.
- `unwrap(): mixed` — value or throw the error (throws original Throwable or `RuntimeException`).
- `unwrapOr(mixed $default): mixed`
- `unwrapOrElse(callable $fn): mixed`
- `getOrThrow(callable $exceptionFactory): mixed` — map error to custom exception and throw.
- `throwIfFail(): Result` — throw on failure, otherwise return `$this` (useful after `thenUnsafe()`).
