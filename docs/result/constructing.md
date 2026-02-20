---
title: Constructing Results
---

# Constructing Results

_Reading time: ~5 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Task summary

Use construction methods to start a flow with explicit branch semantics and aggregate pre-built `Result` values.

Deep dives:
- Core flow composition: [Core Pipelines](/result/compositions/core-pipelines)
- Failure composition: [Failure and Recovery](/result/compositions/failure-recovery)
- Method contracts: [API Reference](/api#static-constructors-and-aggregators)

## Quick mental model

- `ok` and `fail` choose your branch explicitly.
- `of` and `defer` normalize callback outcomes into `Result`.
- `combine` and `combineAll` aggregate existing `Result[]` with different failure strategies.
- Metadata stays attached to the branch unless you replace it.

## Primary methods

- `ok`, `fail`, `failWithValue`: explicit branch constructors.
- `of`, `defer`: wrap thrown exceptions and normalize callback output.
- `combine`, `combineAll`: aggregate existing `Result[]`.
- `bracket`: resource-safe acquire/use/release flow.

## When to use `ok` / `defer` / `combine`

| Need | Use |
|---|---|
| You already know success/failure branch | `ok` / `fail` |
| Callback may throw or return `Result` | `defer` |
| Merge existing `Result[]` and stop on first fail | `combine` |
| Merge existing `Result[]` and keep all fails | `combineAll` |

## Worked flow (end-to-end)

### Input

```php
$input = ['email' => 'dev@example.com', 'password' => 'secret123'];
```

### Flow steps

1. Start from a plain input with `defer` so throws are captured.
2. Validate with an explicit `Result` branch.
3. Build a second `Result` for profile defaults.
4. Aggregate with `combine` to fail fast if any step failed.

### Output

- Success sample:

```php
[
  'ok' => true,
  'value' => [
    ['email' => 'dev@example.com', 'password' => 'secret123'],
    ['role' => 'user'],
  ],
  'error' => null,
  'meta' => ['step' => 'defaults'],
]
```

- Failure sample:

```php
[
  'ok' => false,
  'value' => null,
  'error' => 'Invalid email',
  'meta' => ['field' => 'email'],
]
```

## Copy-paste snippet

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$input = ['email' => 'dev@example.com', 'password' => 'secret123'];

$validated = Result::defer(fn (): array => $input)
    ->then(function (array $payload): Result {
        if (! filter_var($payload['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
            return Result::fail('Invalid email', ['field' => 'email']);
        }

        return Result::ok($payload, ['step' => 'validated']);
    });

$defaults = Result::ok(['role' => 'user'], ['step' => 'defaults']);

$result = Result::combine([$validated, $defaults]);

print_r($result->toArray());
```

## Failure demo

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;
use RuntimeException;

$result = Result::defer(fn () => throw new RuntimeException('Acquire failed'));
print_r($result->toArray());
```

Expected shape: `ok=false`, `error` is a `RuntimeException`, `value=null`.

## Common beginner mistakes

- Using `fail` when you need to preserve failed input (`failWithValue` is better there).
- Choosing `of` when callback may already return a `Result` (`defer` is the right fit).
- Using `combine` when you actually need all errors (`combineAll` or `mapCollectErrors`).
- Forgetting to include metadata at construction time for traceability.

## Try it

- `php examples\defer\defer-test.php`
- `php examples\defer\bracket-test.php`

## Related pages

- [Composition Patterns](/result/compositions)
- [Batch Processing](/result/batch-processing)
- [Chaining and Transforming](/result/chaining)
