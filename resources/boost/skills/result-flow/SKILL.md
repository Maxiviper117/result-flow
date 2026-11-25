---
name: result-flow
description: Build and review synchronous Result Flow v2 chains, failure handling, metadata, and application boundaries.
---

# Result Flow v2

Use `Result::ok` and `Result::fail` for expected outcomes. Prefer `map` for plain value transformations and `andThen` for Result-returning actions. `mapError` transforms a failure and `orElse` returns a replacement Result. All of these callbacks receive one payload; exceptions propagate. Selective capture is explicit with `Result::attempt($fn, catch: ExpectedException::class)`.

End chains with `match` at boundaries. Only `unwrap` when throwing is intentional; it rethrows Throwable failures and otherwise throws `UnwrapFailure` retaining the original failure. Returning a failure from a Laravel transaction does not roll it back; throw inside the transaction when rollback is needed.

Chaining shallow-merges metadata with child keys winning. `withMeta` replaces the bag. Keep diagnostic metadata out of responses unless explicitly allowlisted. Use the local [API reference](references/api.md) for signatures and helpers.
