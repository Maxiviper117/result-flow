---
title: Getting Started
---

# Getting Started

Install the package:

```bash
composer require maxiviper117/result-flow
```

Then start with the smallest useful flow:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['total' => 42], ['request_id' => 'r-1'])
    ->ensure(fn (array $order) => $order['total'] > 0, 'Total must be positive')
    ->then(fn (array $order) => Result::ok([
        'status' => 'queued',
        'total' => $order['total'],
    ], ['operation' => 'queue-order']))
    ->otherwise(fn ($error, array $meta) => Result::fail([
        'message' => (string) $error,
        'request_id' => $meta['request_id'] ?? null,
    ], $meta));
```

## How to read it

- `ok(...)` starts a success branch.
- `ensure(...)` keeps success only when the predicate passes.
- `then(...)` runs the next step only on success.
- `otherwise(...)` runs only on failure.
- `match(...)` finishes the flow with an explicit output.

`meta` is part of the result, not an afterthought. Carry request IDs, operation names, and other correlation data from the start.

## Core rules

- Success methods skip failures.
- Failure methods skip successes.
- `then(...)` catches thrown exceptions and turns them into failure.
- `thenUnsafe(...)` lets exceptions bubble.
- `defer(...)` accepts a callback that may return a plain value or a `Result`.

## Read this next

1. [Result model](/concepts/result-model)
2. [Constructing results](/concepts/constructing)
3. [Chaining](/concepts/chaining)
4. [Failure handling](/concepts/failure-handling)
5. [Finalization boundaries](/concepts/finalization-boundaries)

## Reference pages

- [Construction reference](/reference/construction)
- [Chaining reference](/reference/chaining)
- [Failure handling reference](/reference/failure-handling)
- [Metadata and debugging reference](/reference/metadata-debugging)
- [Batch processing reference](/reference/batch-processing)
