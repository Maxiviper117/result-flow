---
title: Result Guide
---

# Result Guide

This section is task-focused. Use it when you know what outcome you need and want the right `Result` method quickly.

## Mental model

A `Result` is always exactly one of:

```text
Ok(value, meta) | Fail(error, meta)
```

Everything in the API preserves that invariant.

## Method families

- Construction and aggregation: `ok`, `fail`, `of`, `combine`, `combineAll`
- Batch mapping: `mapItems`, `mapAll`, `mapCollectErrors`
- Value transforms: `map`, `mapError`, `ensure`
- Success chaining: `then`, `flatMap`, `thenUnsafe`
- Failure handling: `otherwise`, `catchException`, `recover`
- Finalization: `match`, `unwrap*`, `toJson`, `toResponse`

## Choose this when

| Need | Use |
|---|---|
| Per-item outcomes | `mapItems` |
| Stop at first batch failure | `mapAll` |
| Collect all batch failures | `mapCollectErrors` or `combineAll` |
| Transform success value only | `map` |
| Run a step that returns `Result` | `then` / `flatMap` |
| Recover failed result into success | `recover` |
| Handle both branches explicitly | `match` |
| Throw on fail in transactions | `throwIfFail` / `thenUnsafe` |

## Deep-dive pages

- [Constructing Results](/result/constructing)
- [Chaining and Transforming](/result/chaining)
- [Error Handling](/result/error-handling)
- [Batch Processing](/result/batch-processing)
- [Retrying](/result/retrying)
- [Matching and Unwrapping](/result/matching-unwrapping)
- [Metadata and Debugging](/result/metadata-debugging)
- [Transformers](/result/transformers)

For method-by-method contracts, use [API Reference](/api).
