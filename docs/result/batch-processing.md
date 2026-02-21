---
title: Batch Processing
---

# Batch Processing

_Reading time: ~5 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Task summary

Use batch methods when each item can succeed or fail independently and you need explicit aggregation behavior.

Deep dives:
- Pipeline composition baseline: [Core Pipelines](/result/compositions/core-pipelines)
- Failure semantics: [Failure and Recovery](/result/compositions/failure-recovery)
- Contracts: [API Reference](/api#static-constructors-and-aggregators)

## Quick mental model

- `mapItems` returns one `Result` per item.
- `mapAll` stops on first failure.
- `mapCollectErrors` evaluates all items and returns keyed errors.
- `combine`/`combineAll` are for aggregating already-built `Result[]`.

## Primary methods

- `mapItems`: per-item results with preserved keys.
- `mapAll`: fail-fast aggregate from raw items.
- `mapCollectErrors`: collect-all aggregate from raw items.
- `combine`: fail-fast aggregate from existing `Result[]`.
- `combineAll`: collect-all aggregate from existing `Result[]`.

## When to use `mapAll` vs `mapCollectErrors`

| Need | Method |
|---|---|
| Stop immediately on first failure | `mapAll` |
| Return all failures for full reporting | `mapCollectErrors` |
| Keep per-item `Result` for custom post-processing | `mapItems` |

## Worked flow (end-to-end)

### Input

```php
$rows = [
    'a' => ['email' => 'a@example.com'],
    'b' => ['email' => 'bad-email'],
    'c' => ['email' => 'c@example.com'],
];
```

### Flow steps

1. Validate each row into `Result`.
2. Run `mapCollectErrors` to process all rows.
3. Return success map or keyed error map.

### Output

- Success sample:

```php
[
  'ok' => true,
  'value' => [
    'a' => ['email' => 'a@example.com'],
    'b' => ['email' => 'b@example.com'],
  ],
  'error' => null,
  'meta' => [],
]
```

- Failure sample:

```php
[
  'ok' => false,
  'value' => null,
  'error' => ['b' => 'Invalid email at b'],
  'meta' => [],
]
```

## Copy-paste snippet

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$rows = [
    'a' => ['email' => 'a@example.com'],
    'b' => ['email' => 'bad-email'],
    'c' => ['email' => 'c@example.com'],
];

$validator = function (array $row, string $key): Result {
    if (! filter_var($row['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
        return Result::fail("Invalid email at {$key}");
    }

    return Result::ok(['email' => strtolower($row['email'])]);
};

$result = Result::mapCollectErrors($rows, $validator);

print_r($result->toArray());
```

## Failure demo

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$rows = ['x' => ['email' => 'not-an-email']];

$result = Result::mapAll($rows, function (array $row): Result {
    if (! filter_var($row['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
        return Result::fail('Invalid email');
    }

    return Result::ok($row);
});

print_r($result->toArray());
```

Expected behavior: fail-fast on first invalid row.

## Common beginner mistakes

- Choosing `mapAll` when the UI needs all validation errors.
- Expecting `mapItems` to aggregate automatically.
- Forgetting keys are preserved (which is useful for form maps).
- Mixing raw items and `Result[]` instead of using the right method family.

## Try it

- `php examples\batch\batch-map-demo.php`

## Related pages

- [Constructing Results](/result/constructing)
- [Error Handling](/result/error-handling)
- [API Reference](/api)
