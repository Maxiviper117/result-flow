---
title: Retrying
---

# Retrying

_Reading time: ~5 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Task summary

Use retry APIs for transient failures with explicit stop conditions and observable attempt behavior.

Deep dives:
- Failure mapping after retries: [Failure and Recovery](/result/compositions/failure-recovery)
- Metadata tracking: [Metadata and Observability](/result/compositions/metadata-observability)
- Contracts: [API Reference](/api#static-constructors-and-aggregators)

## Quick mental model

- `retry` is simple and policy-light.
- `retryDefer` normalizes each attempt like `defer`.
- `retrier` is for advanced backoff/predicate/hook control.
- Retry decides whether to try again; failure handling still happens afterward.

## Primary methods

- `Result::retry`: simple retry entrypoint.
- `Result::retryDefer`: retry with defer-style normalization.
- `Result::retrier`: fluent policy builder (attempt limits, backoff, jitter, hooks, predicates).

## When to use `retry` vs `retryDefer` vs `retrier`

| Need | Method |
|---|---|
| Small retry policy, callback already returns stable result type | `retry` |
| Callback may throw or return value/`Result` | `retryDefer` |
| Custom retry predicate, hooks, and backoff | `retrier` |

## Worked flow (end-to-end)

### Input

```php
$attempt = 0;
$send = function () use (&$attempt): array {
    $attempt++;
    if ($attempt < 3) {
        throw new RuntimeException("Attempt {$attempt} failed");
    }
    return ['ok' => true, 'attempt' => $attempt];
};
```

### Flow steps

1. Call `retryDefer` with max attempts.
2. First two attempts fail via exceptions.
3. Third attempt succeeds and returns success value.
4. Handle terminal failure via `otherwise` when needed.

### Output

- Success sample:

```php
[
  'ok' => true,
  'value' => ['ok' => true, 'attempt' => 3],
  'error' => null,
  'meta' => [],
]
```

- Failure sample (all attempts fail):

```php
[
  'ok' => false,
  'value' => null,
  'error' => RuntimeException('Attempt 3 failed'),
  'meta' => [],
]
```

## Copy-paste snippet

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;
use RuntimeException;

$attempt = 0;
$send = function () use (&$attempt): array {
    $attempt++;
    if ($attempt < 3) {
        throw new RuntimeException("Attempt {$attempt} failed");
    }

    return ['ok' => true, 'attempt' => $attempt];
};

$result = Result::retryDefer(
    times: 3,
    fn: $send,
    delay: 0,
    exponential: false,
)->otherwise(fn ($error): Result => Result::fail("Retry exhausted: {$error}"));

print_r($result->toArray());
```

## Failure demo

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;
use RuntimeException;

$result = Result::retryDefer(
    times: 2,
    fn: fn () => throw new RuntimeException('Always failing'),
);

print_r($result->toArray());
```

Expected behavior: failure after retry budget is exhausted.

## Common beginner mistakes

- Treating retry as error handling replacement (you still need failure mapping).
- Retrying non-transient validation/domain errors.
- Forgetting `retry` and `retryDefer` differ in callback normalization needs.
- Setting long retry windows without observability metadata.

## Try it

- `php examples\retry\retry-test.php`
- `php examples\retry\retry-defer-test.php`

## Related pages

- [Error Handling](/result/error-handling)
- [Metadata and Debugging](/result/metadata-debugging)
- [API Reference](/api)
