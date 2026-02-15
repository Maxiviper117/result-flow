---
title: Getting Started
---

# Getting Started

## Install

```bash
composer require maxiviper117/result-flow
```

## First pipeline

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['order_id' => 123, 'total' => 42], ['request_id' => 'r-1'])
    ->ensure(fn (array $order) => $order['total'] > 0, 'Order total must be positive')
    ->then(fn (array $order, array $meta) => Result::ok([
        'id' => $order['order_id'],
        'status' => 'queued',
    ], [...$meta, 'step' => 'queued']))
    ->otherwise(fn ($error, array $meta) => Result::fail("Order pipeline failed: {$error}", $meta));

$response = $result->match(
    onSuccess: fn (array $value) => ['ok' => true, 'data' => $value],
    onFailure: fn ($error) => ['ok' => false, 'error' => (string) $error],
);
```

## Core rules

- Success-path methods (`then`, `map`, `ensure`) run only when result is ok.
- Failure-path methods (`otherwise`, `catchException`, `recover`) run only when result is failed.
- Metadata is carried through every step unless you explicitly replace it.
- `then()` catches thrown exceptions and converts them to `Result::fail(Throwable)`.
- `thenUnsafe()` does not catch; exceptions bubble.

## Batch processing in one glance

- `Result::mapItems($items, $fn)` returns one `Result` per item.
- `Result::mapAll($items, $fn)` fails fast on first error.
- `Result::mapCollectErrors($items, $fn)` evaluates all items and returns keyed errors.

See [Batch Processing](/result/batch-processing) for full behavior and examples.

## Laravel Boost (optional)

- This package ships Laravel Boost assets for AI-assisted ResultFlow usage.
- Included guideline: `resources/boost/guidelines/core.blade.php`.
- Included skills:
  - `resources/boost/skills/result-flow-laravel/SKILL.md`
  - `resources/boost/skills/result-flow-debugging/SKILL.md`
- Quick reference and override paths: [Laravel Boost](/laravel-boost).

## What to read next

- [Result Guide](/result/)
- [API Reference](/api)
- [Examples](/examples/)
