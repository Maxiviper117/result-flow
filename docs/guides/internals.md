---
title: Internals
---

# Internals

## What this page is for

Use this page when contributing to Result Flow or reasoning about implementation-level behavior.

## Core invariants

- `Result` is immutable after creation.
- Exactly one branch is active at any time (`ok` xor `fail`).
- Metadata is always an array and always present.

## Internal support components

- `Support\Operations\Pipeline`: handles callable/object/step-array invocation.
- `Support\Traits\Transform`: `map`, `mapError`, `ensure`, `recover` behavior.
- `Support\Traits\Matcher`: `match`, `matchException`, `catchException`.
- `Support\Traits\Unwrap`: unwrap/throw family.
- `Support\Operations\Batch`: `mapItems`, `mapAll`, `mapCollectErrors`.
- `Support\Output\Serialization`: array/json/xml conversions.
- `Support\Traits\MetaOps`: metadata mapping/merge/tap.

## Metadata merge rule

Where methods aggregate multiple results, metadata is merged in processing order.
Later keys overwrite earlier keys.

## Exception normalization boundaries

- `of()` captures throw -> fail.
- `then()` captures throw -> fail.
- `thenUnsafe()` does not capture.
- Batch methods capture callback exceptions per item as `fail(Throwable)`.

## Type-safety goals

Public PHPDoc templates are designed for PHPStan/Psalm awareness.
When changing method signatures, preserve generic intent and branch typing.

## Related pages

- [API Reference](/api)
- [Testing Recipes](/testing)
- [Contributing](https://github.com/Maxiviper117/result-flow/blob/main/CONTRIBUTING.md)
