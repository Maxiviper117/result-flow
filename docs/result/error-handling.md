---
title: Error Handling
---

# Error Handling

_Reading time: ~6 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Task summary

Use failure-path methods to normalize errors, branch by exception type, and recover only when intentional.

Deep dives:
- Failure-path design: [Failure and Recovery](/result/compositions/failure-recovery)
- End-of-flow choices: [Finalization Boundaries](/result/compositions/finalization-boundaries)
- Contracts: [API Reference](/api#failure-branch-handlers)

## Quick mental model

- `otherwise` is to failure branch what `then` is to success branch.
- `catchException` only handles Throwable errors.
- `recover` always converts failure into success.
- `throwIfFail` restores exception-style control flow.

## Primary methods

- `otherwise`: failure-branch counterpart to `then`.
- `catchException`: class-based handling for Throwable failures.
- `recover`: always convert failure into success.
- `throwIfFail`: escalate failure channel to exceptions.

## When to use `otherwise` vs `recover` vs `throwIfFail`

| Need | Method |
|---|---|
| Map failure and stay failed or recover conditionally | `otherwise` |
| Always return success fallback | `recover` |
| Convert failure to thrown exception at boundary | `throwIfFail` |

## Worked flow (end-to-end)

### Input

```php
$gatewayCall = fn (): array => throw new RuntimeException('Timeout');
```

### Flow steps

1. Wrap call with `of`.
2. `catchException` maps runtime exception to stable error shape.
3. `otherwise` appends operation context.
4. `recover` returns fallback success for caller.

### Output

- Success sample:

```php
[
  'ok' => true,
  'value' => ['fallback' => true, 'reason' => 'UPSTREAM_TIMEOUT', 'request_id' => 'r-200'],
  'error' => null,
  'meta' => ['request_id' => 'r-200', 'operation' => 'gateway.send'],
]
```

- Failure sample (if you remove `recover`):

```php
[
  'ok' => false,
  'value' => null,
  'error' => ['code' => 'UPSTREAM_TIMEOUT', 'message' => 'Timeout', 'operation' => 'gateway.send'],
  'meta' => ['request_id' => 'r-200', 'operation' => 'gateway.send'],
]
```

## Copy-paste snippet

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;
use RuntimeException;

$gatewayCall = function (): array {
    throw new RuntimeException('Timeout');
};

$result = Result::of($gatewayCall)
    ->mergeMeta(['request_id' => 'r-200', 'operation' => 'gateway.send'])
    ->catchException([
        RuntimeException::class => fn (RuntimeException $e, array $meta): Result => Result::fail([
            'code' => 'UPSTREAM_TIMEOUT',
            'message' => $e->getMessage(),
        ], $meta),
    ])
    ->otherwise(fn (array $error, array $meta): Result => Result::fail([
        ...$error,
        'operation' => $meta['operation'] ?? 'unknown',
    ], $meta))
    ->recover(fn (array $error, array $meta): array => [
        'fallback' => true,
        'reason' => $error['code'] ?? 'unknown',
        'request_id' => $meta['request_id'] ?? null,
    ]);

print_r($result->toArray());
```

## Failure demo

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$result = Result::fail('timeout')
    ->throwIfFail();
```

Expected behavior: throws `RuntimeException` (non-Throwable error channel).

## Common beginner mistakes

- Expecting `catchException` to handle non-Throwable errors.
- Recovering too early and hiding useful failure context.
- Returning plain values in `otherwise` accidentally recovering.
- Calling `throwIfFail` deep inside a flow instead of at boundary.

## Try it

- `php examples\retry\retry-test.php`
- `php examples\retry\retry-defer-test.php`

## Related pages

- [Core Pipelines](/result/compositions/core-pipelines)
- [Matching and Unwrapping](/result/matching-unwrapping)
- [API Reference](/api)
