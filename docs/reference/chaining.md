---
title: Chaining Reference
---

# Chaining Reference

## `map(callable $map): Result`

Transforms the success value.

## `mapError(callable $map): Result`

Transforms the failure value.

## `ensure(callable $predicate, mixed $error): Result`

Keeps a success only when the predicate returns `true`.

If the predicate returns `false`, the result becomes failure. The error may be a value or a factory callback.

## `then(callable|object|array $next): Result`

Runs the next step only on success.

- step may return a plain value or a `Result`
- thrown exceptions are converted to failure
- arrays of steps are treated as a pipeline
- callable arrays like `[$service, 'handle']` remain one step
- objects with `__invoke`, `handle`, or `execute` are supported

When a step throws, the result gets `meta['failed_step']` with the best available step name.

## `flatMap(callable $fn): Result`

Alias of `then(...)`.

## `thenUnsafe(callable|object $next): Result`

Same chaining behavior as `then(...)`, but exceptions bubble.

## Related pages

- [Chaining concepts](/concepts/chaining)
- [Transaction rollback](/guides/transaction-rollback)
