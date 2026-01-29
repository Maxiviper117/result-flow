---
title: Result class overview
---

# Result class overview

`Maxiviper117\ResultFlow\Result` is the single class exported by Result Flow. It packages a success payload or a failure payload plus traveling metadata. This section walks through the mental model and links to task-focused pages with deeper examples.

## What makes `Result` different?

- Branch-aware: every chain step is either on the success path (`then`, `map`, `ensure`) or the failure path (`otherwise`, `catchException`).
- Metadata stays attached: correlation IDs, request context, and debug crumbs are propagated automatically with each method.
- Typed all the way: PHPStan/Psalm templates (`Result<TSuccess, TFailure>`) flow through chains so you know whether you are handling strings, DTOs, Throwables, or arrays of errors.
- Exception friendly: wrapping code in `Result::of()` or using `then()` converts thrown exceptions into failures while keeping the original exception available for logging or matching.

## Mental model

Think of `Result` as a value that is always in exactly one of two states:

```
Ok(value, meta) | Fail(error, meta)
```

When you call success-path methods (`then`, `map`, `ensure`), they run only if the result is ok. When you call failure-path methods (`otherwise`, `catchException`), they run only if the result is failed. This avoids nested `if` statements and keeps pipelines readable.

## Pipeline lifecycle

A typical pipeline looks like:
1. Construct a Result (`ok`, `fail`, or `of`).
2. Transform or validate on the success path (`map`, `ensure`, `then`).
3. Recover or adjust on the failure path (`otherwise`, `catchException`, `recover`).
4. Finish by converting to a value/response (`match`, `unwrap*`, `toResponse`).

## Where to finish a pipeline

Most pipelines end by converting the result into a value, response, or exception:
- `match()` to handle both branches explicitly
- `unwrap*()` when you want a value or exception
- `toResponse()` when returning HTTP responses

## Quick navigation

- [Construct and combine results](/result/constructing)
- [Transform and chain results](/result/chaining)
- [Retrying operations](/result/retrying)
- [Match, unwrap, and recover](/result/matching-unwrapping)
- [Output transformers (JSON, XML)](/result/transformers)
- [Metadata, taps, and debugging](/result/metadata-debugging)
- [API reference](/api) (full method catalog with examples)
