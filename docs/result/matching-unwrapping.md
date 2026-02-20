---
title: Matching and Unwrapping
---

# Matching and Unwrapping

_Reading time: ~5 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Task summary

Use these methods to finalize a pipeline into plain values, explicit branch outputs, or thrown exceptions.

Deep dives:
- Boundary strategy: [Finalization Boundaries](/result/compositions/finalization-boundaries)
- Failure-path composition: [Failure and Recovery](/result/compositions/failure-recovery)
- Contracts: [API Reference](/api#matching-and-unwrapping)

## Quick mental model

- `match` handles both branches explicitly.
- `unwrap*` extracts plain values with different fallback/throw behavior.
- `throwIfFail` is for exception-required boundaries.
- Choose one finalization style per boundary function.

## Primary methods

- `match`: explicit branch completion without implicit throws.
- `matchException`: class-aware exception matching for failure branch.
- `unwrap`, `unwrapOr`, `unwrapOrElse`, `getOrThrow`: value extraction variants.
- `throwIfFail`: throw on failure and preserve success result.

## When to use `match` vs `unwrap*` vs `throwIfFail`

| Need | Method |
|---|---|
| Return branch-aware output object | `match` |
| Return plain value with fallback/throw strategy | `unwrap*` |
| Force exception semantics at boundary | `throwIfFail` |

## Worked flow (end-to-end)

### Input

```php
$result = Result::fail('Missing order');
```

### Flow steps

1. Branch explicitly with `match` for API-style payload.
2. Use `unwrapOrElse` when caller needs plain fallback value.
3. Keep throwing behavior (`unwrap`/`throwIfFail`) only for exception boundaries.

### Output

- `match` sample:

```php
['ok' => false, 'error' => 'Missing order', 'meta' => ['request_id' => 'r-300']]
```

- `unwrapOrElse` sample:

```php
['id' => null, 'reason' => 'Missing order', 'request_id' => 'r-300']
```

## Copy-paste snippet

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$result = Result::fail('Missing order', ['request_id' => 'r-300']);

$apiPayload = $result->match(
    onSuccess: fn (mixed $value, array $meta): array => ['ok' => true, 'data' => $value, 'meta' => $meta],
    onFailure: fn (mixed $error, array $meta): array => ['ok' => false, 'error' => (string) $error, 'meta' => $meta],
);

$fallbackValue = $result->unwrapOrElse(
    fn (mixed $error, array $meta): array => ['id' => null, 'reason' => (string) $error, 'request_id' => $meta['request_id'] ?? null]
);

print_r($apiPayload);
print_r($fallbackValue);
```

## Failure demo

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$result = Result::fail('Boom');
$result->unwrap();
```

Expected behavior: throws because branch is failure.

## Common beginner mistakes

- Calling `unwrap` in normal business flows where failure is expected.
- Mixing `match` and `unwrap` in the same boundary function.
- Forgetting `unwrapOr` is eager, while `unwrapOrElse` is lazy.
- Using `throwIfFail` without mapping non-Throwable errors first.

## Try it

- `php examples\defer\defer-test.php`
- `php examples\defer\bracket-test.php`

## Related pages

- [Finalization Boundaries](/result/compositions/finalization-boundaries)
- [Transformers](/result/transformers)
- [API Reference](/api)
