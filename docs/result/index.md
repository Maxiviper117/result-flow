---
title: Result Guide
---

# Result Guide

This section is task-focused. Use it to choose the right method family quickly, then jump into composition deep dives.

## Mental model

A `Result` is exactly one of:

```text
Ok(value, meta) | Fail(error, meta)
```

Composition keeps that invariant explicit through every step.

## Choose your depth

- Foundations and operations (quick method selection): this `result/` section.
- Composition lanes (Effect-style behavior and combinations): [Composition Patterns](/result/compositions)
- Exact signatures and contracts: [API Reference](/api)

If you are new, read in this order: [Constructing Results](/result/constructing) -> [Chaining and Transforming](/result/chaining) -> [Error Handling](/result/error-handling) -> [Matching and Unwrapping](/result/matching-unwrapping).

## Method families

- Construction and aggregation: `ok`, `fail`, `of`, `defer`, `combine`, `combineAll`
- Batch mapping: `mapItems`, `mapAll`, `mapCollectErrors`
- Value transforms: `map`, `mapError`, `ensure`
- Success chaining: `then`, `flatMap`, `thenUnsafe`
- Failure handling: `otherwise`, `catchException`, `recover`
- Finalization: `match`, `unwrap*`, `toJson`, `toResponse`

## Start from concept lanes

| Lane | Use |
|---|---|
| Foundations | [Result Overview](/result/), [Constructing Results](/result/constructing), [Chaining and Transforming](/result/chaining) |
| Compositions | [Composition Patterns](/result/compositions), [Core Pipelines](/result/compositions/core-pipelines), [Failure and Recovery](/result/compositions/failure-recovery), [Finalization Boundaries](/result/compositions/finalization-boundaries), [Metadata and Observability](/result/compositions/metadata-observability) |
| Operational flows | [Error Handling](/result/error-handling), [Retrying](/result/retrying), [Batch Processing](/result/batch-processing) |
| Boundaries and output | [Matching and Unwrapping](/result/matching-unwrapping), [Transformers](/result/transformers), [Metadata and Debugging](/result/metadata-debugging) |
