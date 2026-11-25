# Migrating from v1 to v2

V2 is a breaking release. Keep 1.x available while migrating, and review each boundary and transaction; do not mechanically rename methods. Normal v2 callbacks do not catch exceptions and receive one argument, not payload plus metadata.

| V1 | V2 migration and review |
| --- | --- |
| `then`, `flatMap`, `thenUnsafe` | Choose `map` for a plain value or `andThen` for a Result. Expand arrays into steps and use PHP callables. Move needed exception capture to the relevant boundary. |
| `otherwise`, `recover`, `catchError`, `catchException` | Use `orElse` for Result-returning recovery and `mapError` for normalization. Use `instanceof` or PHP `match` in your own callback for class-specific behavior; return `Result::fail($error)` to retain an unhandled error. |
| `of`, `defer` | `attempt` wraps a value and catches only the selected class. For an operation that already returns a Result, use explicit `try/catch` to avoid nesting. |
| `value`, `error`, `unwrapOrElse`, `getOrThrow`, `throwIfFail` | Use `match`, or deliberately `unwrap` when a throw is needed. Use `match` for lazy terminal fallback. Never infer the branch from a nullable payload. |
| `ensure`, `failTagged`, `failWithValue` | Return typed validation failures, `fail($domainError)`, or attach explicit metadata. Existing structured error classes remain available. |
| taps and per-key metadata methods | Use a named observation checkpoint and `withMeta([...$result->meta(), ...$patch])` for a merge. Capture context explicitly. |
| `combine`, `combineAll`, `mapItems`, `mapAll`, `mapCollectErrors` | Choose a `ResultBatch` operation based on whether Results are already evaluated and whether all errors are required. Keys are preserved, which can differ from v1 combine. |
| retry / bracket | Use `Retry::config()` or `ResourceScope::run()`. These compatibility helpers capture exceptions; configure retry predicates for transient errors. |
| serialization / response methods | Use explicit `Output\LegacyOutput` or `Laravel\LegacyResponse` to preserve the old wire shape and status codes, including metadata. Prefer explicit domain response mapping for new endpoints. |

## Metadata and callbacks

V1 chains could replace parent metadata when a callback returned a Result. V2 shallow-merges parent then child; child wins even for null. Nested arrays are replaced. An empty child does not reset the parent. Clear with `withMeta([])` on the final output. Manually review every two-argument callback and every place that relied on implicit metadata replacement or caught exceptions.

## Representative migrations

- Validate and save: use a plain `map` normalization followed by `andThen` validation and persistence; see [validate then persist](/guides/validate-then-persist).
- Transaction: returning Fail normally commits. Throw inside the closure if a prior write must roll back and handle it outside; see [transaction rollback](/guides/transaction-rollback).
- Validation loop: use `ResultBatch::mapAllErrors` to visit every item and return errors by key; see [collecting errors](/recipes/collecting-batch-errors).
- Legacy endpoint: use `LegacyResponse::fromResult($result)` temporarily and compare status, headers and body with the existing contract. It includes metadata; ensure the existing endpoint does not expose diagnostics. For a new mapping, see [API boundary mapping](/recipes/api-boundary-mapping).

## Documentation path changes

The v1 construction/deferred topics map to [construction](/reference/construction); old chain, match and recovery topics map to [chaining](/reference/chaining) and [failure handling](/reference/failure-handling). Former batch concepts map to [batch strategy](/guides/batch-strategy) and [batch reference](/reference/batch-processing). Former resource and retry concept pages map to [resource cleanup](/recipes/resource-cleanup) and the helper discussion below. Former finalization pages map to [boundaries](/reference/boundaries); observability and metadata topics map to [metadata and debugging](/reference/metadata-debugging). Removed input-validation and error-normalization pages map to [validate then persist](/guides/validate-then-persist) and [failure handling](/reference/failure-handling).

## Standalone retry

```php
use Maxiviper117\ResultFlow\Retry;

$result = Retry::config()->maxAttempts(3)
    ->when(fn ($error) => $error instanceof TransientFailure)
    ->attempt(fn () => $remote->fetch());
```

The builder also supports `delay`, `exponential`, `jitter`, `onRetry`, and `attachAttemptMeta`. Delay and jitter introduce waiting and randomness in the helper; core Result operations do not. Captured throwables and returned Fail values are subject to the retry predicate. `ResourceScope::run` is documented in [resource cleanup](/recipes/resource-cleanup).

## Prerelease delivery

V1 remains available through its existing releases and documentation. Pull requests build an unpublished v2 documentation artifact with a separate preview base; they do not deploy the stable site. Maintainers should publish a 2.0 prerelease and its documentation separately before promoting a stable major. Release tags are managed by maintainers.
