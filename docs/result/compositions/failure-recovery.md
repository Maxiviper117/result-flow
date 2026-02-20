---
title: Failure and Recovery
---

# Failure and Recovery

_Reading time: ~6 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Overview

This page focuses on failure-path composition:

```text
otherwise -> catchException -> recover
```

Use it to normalize error shapes, branch by exception class, and choose explicit recovery points.

## Default behavior

- `otherwise` runs only on failed results and may recover or keep failure.
- `catchException` only handles Throwable failures and class-matches handlers in order.
- `recover` always converts failure to success.
- Metadata remains available in every handler callback.

## When to use

- You need stable error payload contracts for callers.
- Different exception classes require different fallback behavior.
- You intentionally degrade to a safe success fallback.

## When not to use

- You need strict failure propagation all the way to boundary.
- You rely on thrown exceptions for transactional semantics.
- Error normalization belongs at a single outer boundary only.

## Composes with

- [`otherwise`](/api#otherwise-callable-object-array-next-result)
- [`catchException`](/api#catchexception-array-handlers-callable-fallback-null-result)
- [`recover`](/api#recover-callable-fn-result)
- [`mapError`](/api#maperror-callable-map-result)
- [`matchException`](/api#matchexception-array-exceptionhandlers-callable-onsuccess-callable-onunhandled-mixed)

## Example progression

### Minimal snippet

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$callService = fn (): Result => Result::fail('timeout');

$result = $callService()
    ->otherwise(fn ($error, array $meta) => Result::fail(['message' => (string) $error], $meta));
```

### Production-shaped snippet

```php
<?php

declare(strict_types=1);

use InvalidArgumentException;
use Maxiviper117\ResultFlow\Result;
use RuntimeException;

$result = Result::of(fn () => $gateway->send($payload))
    ->catchException([
        InvalidArgumentException::class => fn (InvalidArgumentException $e, array $meta) => Result::fail([
            'message' => $e->getMessage(),
            'code' => 'INVALID_ARGUMENT',
        ], $meta),
        RuntimeException::class => fn (RuntimeException $e, array $meta) => Result::fail([
            'message' => 'Temporary upstream issue',
            'code' => 'UPSTREAM_RUNTIME',
        ], [...$meta, 'root_error' => $e->getMessage()]),
    ], fallback: fn ($error, array $meta) => Result::fail([
        'message' => (string) $error,
        'code' => 'UNHANDLED_FAILURE',
    ], $meta))
    ->recover(fn ($error, array $meta) => [
        'fallback' => true,
        'reason' => $error['code'] ?? 'unknown',
        'request_id' => $meta['request_id'] ?? null,
    ]);
```

Try it:
- `php examples\retry\retry-test.php`

## Failure modes and edge cases

- `catchException` skips non-Throwable failures; pair with `otherwise` when error channel is mixed.
- `recover` can hide failures; annotate metadata so downstream callers know fallback was used.
- Handler ordering matters when classes share inheritance.

## Related API entries

- [Failure branch handlers](/api#failure-branch-handlers)
- [Matching and unwrapping](/api#matching-and-unwrapping)
- [Tap and inspection methods](/api#tap-and-inspection-methods)
