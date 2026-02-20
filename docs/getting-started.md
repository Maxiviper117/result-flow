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

## Read in this order (first-time users)

1. [Result Guide](/result/)
2. [Constructing Results](/result/constructing)
3. [Chaining and Transforming](/result/chaining)
4. [Error Handling](/result/error-handling)
5. [Matching and Unwrapping](/result/matching-unwrapping)
6. [Composition Patterns](/result/compositions)
7. [Examples](/examples/)

## Learn by concept lane

- Foundations: [Result Guide](/result/), [Constructing Results](/result/constructing), [Chaining and Transforming](/result/chaining)
- Core composition: [Composition Patterns](/result/compositions), [Core Pipelines](/result/compositions/core-pipelines)
- Failure lane: [Failure and Recovery](/result/compositions/failure-recovery), [Error Handling](/result/error-handling)
- Boundary lane: [Finalization Boundaries](/result/compositions/finalization-boundaries), [Matching and Unwrapping](/result/matching-unwrapping), [Transformers](/result/transformers)
- Observability lane: [Metadata and Observability](/result/compositions/metadata-observability), [Metadata and Debugging](/result/metadata-debugging)

## Batch processing in one glance

- `Result::mapItems($items, $fn)` returns one `Result` per item.
- `Result::mapAll($items, $fn)` fails fast on first error.
- `Result::mapCollectErrors($items, $fn)` evaluates all items and returns keyed errors.

See [Batch Processing](/result/batch-processing) for full behavior and examples.

## Laravel Boost (optional)

- This package ships Boost source assets that guide AI behavior in downstream consumer Laravel apps.
- Package-shipped guideline source: `resources/boost/guidelines/core.blade.php`.
- Package-shipped skill sources:
  - `resources/boost/skills/result-flow-laravel/SKILL.md`
  - `resources/boost/skills/result-flow-debugging/SKILL.md`
- In your app, install Boost and apply package guidance there. See [Laravel Boost](/laravel-boost).

## What to read next

- [Result Guide](/result/)
- [Composition Patterns](/result/compositions)
- [Examples](/examples/)
- [API Reference](/api)
