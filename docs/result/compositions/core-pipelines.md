---
title: Core Pipelines
---

# Core Pipelines

_Reading time: ~6 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Overview

This page covers the default pipeline shape:

```text
of/defer -> ensure -> then/flatMap -> otherwise/recover -> match/unwrap
```

Use it when you need explicit branch handling with predictable metadata propagation.

## Default behavior

- `of` and `defer` capture throws as `fail(Throwable)`.
- `ensure`, `map`, `then`, and `flatMap` run only on success.
- `otherwise`, `catchException`, and `recover` run only on failure.
- Metadata (`array<string,mixed>`) flows through every step unless replaced with `mapMeta`.

## When to use

- Multi-step workflows with expected validation or domain failures.
- Service composition where exceptions should become explicit failure values.
- Pipelines that must keep correlation metadata (`request_id`, `trace_id`, `operation`).

## When not to use

- One-line operations where direct `try/catch` is simpler.
- Performance-critical loops that do not need branch-aware semantics.
- Flows that intentionally rely on exception bubbling (`thenUnsafe` is the better fit there).

## Composes with

- [`Result::of`](/api#result-of-callable-fn-result)
- [`Result::defer`](/api#result-defer-callable-fn-result)
- [`ensure`](/api#ensure-callable-predicate-mixed-error-result)
- [`then` / `flatMap`](/api#then-callable-object-array-next-result)
- [`otherwise`](/api#otherwise-callable-object-array-next-result)
- [`recover`](/api#recover-callable-fn-result)
- [`match` / `unwrap*`](/api#matching-and-unwrapping)

## Example progression

### Minimal snippet

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::defer(fn () => loadInput())
    ->ensure(fn (array $input) => isset($input['email']), 'Email is required')
    ->then(fn (array $input) => saveUser($input))
    ->otherwise(fn ($error, array $meta) => Result::fail("signup failed: {$error}", $meta));
```

### Production-shaped snippet

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::of(fn () => $request->validated())
    ->mergeMeta([
        'request_id' => (string) $request->header('X-Request-Id'),
        'operation' => 'signup.create',
    ])
    ->ensure(fn (array $input) => filter_var($input['email'] ?? null, FILTER_VALIDATE_EMAIL) !== false, 'Invalid email')
    ->then(fn (array $input, array $meta) => $service->create($input, $meta))
    ->otherwise(fn ($error, array $meta) => Result::fail([
        'message' => (string) $error,
        'code' => 'SIGNUP_FAILED',
    ], $meta));

$payload = $result->match(
    onSuccess: fn (array $value, array $meta) => ['ok' => true, 'data' => $value, 'meta' => $meta],
    onFailure: fn ($error, array $meta) => ['ok' => false, 'error' => $error, 'meta' => $meta],
);
```

Try it:
- `php examples\defer\defer-test.php`

## Failure modes and edge cases

- `ensure` error factories should be deterministic and side-effect free.
- Returning plain values in `otherwise` recovers into success; return `Result::fail(...)` to stay failed.
- If you need exception bubbling for transaction rollback, use `thenUnsafe` and finalize with `throwIfFail`.

## Related API entries

- [Construction and aggregation](/api#static-constructors-and-aggregators)
- [Transforming and chaining](/api#transforming-and-chaining)
- [Failure branch handlers](/api#failure-branch-handlers)
- [Matching and unwrapping](/api#matching-and-unwrapping)
