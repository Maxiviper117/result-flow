---
title: FAQ
---

# FAQ

## Why not just throw exceptions everywhere?

Exceptions are still valid. Result Flow helps when you want explicit, branch-aware outcomes that are easier to test and chain.

## When should I use `thenUnsafe()`?

Use it when exceptions must bubble (for example, transaction rollback boundaries).

## How do I choose between `map`, `then`, and `flatMap`?

- `map`: success value -> plain value
- `then`: success value -> `Result` or plain value
- `flatMap`: alias of `then`

## How do I process arrays of items?

- Per-item status: `mapItems`
- Fail-fast aggregate: `mapAll`
- Collect all errors: `mapCollectErrors`

## How do I convert failures into success defaults?

Use `recover`, or use `unwrapOr`/`unwrapOrElse` at boundary points.

## Does metadata survive chaining?

Yes. Metadata propagates through chain methods unless explicitly replaced/overwritten.

## How do I output HTTP responses?

Use `toResponse()`. In Laravel it returns framework response objects. Outside Laravel it returns a normalized array response shape.

## Related pages

- [Getting Started](/getting-started)
- [API Reference](/api)
