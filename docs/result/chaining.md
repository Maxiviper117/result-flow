---
title: Chaining and Transforming
---

# Chaining and Transforming

_Reading time: ~6 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Task summary

Use these methods to transform success values, enforce guards, and chain dependent operations.

Deep dives:
- Pipeline composition: [Core Pipelines](/result/compositions/core-pipelines)
- Boundary decisions: [Finalization Boundaries](/result/compositions/finalization-boundaries)
- Contracts: [API Reference](/api#transforming-and-chaining)

## Quick mental model

- `map` changes success values only.
- `ensure` converts invalid success values into failures.
- `then`/`flatMap` chain operations that may return value or `Result`.
- `thenUnsafe` keeps exception semantics (no capture).

## Primary methods

- `map`: success value to another success value.
- `ensure`: guard successful values and convert failed predicates into failure.
- `then` / `flatMap`: chain steps returning value or `Result` with exception capture.
- `thenUnsafe`: chain without exception capture.
- `mapError`: normalize failure shape while staying failed.

## When to use `map` vs `then` vs `thenUnsafe`

| Need | Method |
|---|---|
| Pure success-value transform | `map` |
| Step may return `Result` or throw (captured) | `then` / `flatMap` |
| Step must throw upward (transaction semantics) | `thenUnsafe` |

## Worked flow (end-to-end)

### Input

```php
$order = ['id' => 42, 'total' => 120];
```

### Flow steps

1. Start with `ok(order)`.
2. `ensure` total is positive.
3. `map` adds derived tax.
4. `then` calls a persistence step that returns `Result`.

### Output

- Success sample:

```php
[
  'ok' => true,
  'value' => [
    'saved' => true,
    'order' => ['id' => 42, 'total' => 120, 'tax' => 12.0],
  ],
  'error' => null,
  'meta' => ['step' => 'persisted'],
]
```

- Failure sample:

```php
[
  'ok' => false,
  'value' => null,
  'error' => 'Invalid total',
  'meta' => ['request_id' => 'r-100'],
]
```

## Copy-paste snippet

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$order = ['id' => 42, 'total' => 120];

$persist = fn (array $payload): Result => Result::ok([
    'saved' => true,
    'order' => $payload,
], ['step' => 'persisted']);

$result = Result::ok($order, ['request_id' => 'r-100'])
    ->ensure(fn (array $o): bool => $o['total'] > 0, 'Invalid total')
    ->map(fn (array $o): array => [...$o, 'tax' => $o['total'] * 0.1])
    ->then(fn (array $o): Result => $persist($o));

print_r($result->toArray());
```

## Failure demo

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['total' => 0])
    ->ensure(fn (array $o): bool => $o['total'] > 0, 'Invalid total');

print_r($result->toArray());
```

Expected shape: `ok=false` with `error='Invalid total'`.

## Common beginner mistakes

- Using `map` when the callback should return a `Result` (use `then`).
- Placing `ensure` after expensive work instead of early guard checks.
- Using `thenUnsafe` without an intentional exception boundary.
- Expecting `mapError` to run on successful results.

## Try it

- `php examples\defer\defer-test.php`
- `php examples\retry\retry-test.php`

## Related pages

- [Failure and Recovery](/result/compositions/failure-recovery)
- [Error Handling](/result/error-handling)
- [API Reference](/api)
