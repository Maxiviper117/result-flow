---
title: Result class overview
---

# Result class overview

`Maxiviper117\\ResultFlow\\Result` is the single class exported by Result Flow. It packages a success payload **or** a failure payload plus traveling metadata. This section walks through the mental model and links to task-focused pages with deeper examples.

## What makes `Result` different?

- **Branch-aware**: every chain step is either on the success path (`then`, `map`, `ensure`) or the failure path (`otherwise`, `catchException`). No more guessing whether a method throws or returns `null`.
- **Metadata stays attached**: correlation IDs, request context, and debug crumbs are propagated automatically with each method.
- **Typed all the way**: PHPStan/Psalm templates (`Result<TSuccess, TFailure>`) flow through chains so you know whether you are handling strings, DTOs, Throwables, or arrays of errors.
- **Exception friendly**: wrapping code in `Result::of()` or using `then()` converts thrown exceptions into failures while keeping the original exception available for logging or matching.

## When to reach for `Result`

- Coordinating multi-step workflows (validation → transformation → persistence) where each step may fail differently.
- Returning errors across service boundaries without throwing (e.g., HTTP/CLI handlers that should not crash the process).
- Capturing additional context (`request_id`, `step`, `user_id`) without manually passing it between functions.
- Mixing exception-based code with explicit success/failure handling.

## Quick navigation

- [Construct and combine results](/result/constructing)
- [Transform and chain results](/result/chaining)
- [Match, unwrap, and recover](/result/matching-unwrapping)
- [Metadata, taps, and debugging](/result/metadata-debugging)
- [API reference](/api)
