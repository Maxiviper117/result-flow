---
title: FAQ
---

# FAQ

## Why use Result Flow instead of throwing everywhere?

Use exceptions for exceptional conditions. Use Result Flow when failure is expected, part of normal flow, and should stay explicit.

## When should I use `thenUnsafe()`?

Only when exception bubbling is the intended boundary behavior, such as transaction rollback.

## What is the difference between `map`, `then`, and `flatMap`?

- `map` transforms a success value
- `then` chains a step that may return a value or a `Result`
- `flatMap` is an alias of `then`

## Does metadata survive chaining?

Yes, unless you replace it with `mapMeta(...)` or return a new result with different metadata.

## What should I use for logs?

Use `toDebugArray()`, not `toArray()`, when the output may contain secrets or long values.

## How do I choose between `mapAll`, `mapCollectErrors`, `combine`, and `combineAll`?

- raw items and fail fast -> `mapAll`
- raw items and collect all errors -> `mapCollectErrors`
- existing `Result[]` and fail fast -> `combine`
- existing `Result[]` and collect all errors -> `combineAll` (returns only failures if any input fails)
