# Public API Whitelist Reference

Use this list to ensure generated code sticks to documented public `Result` APIs.

## Static constructors/utilities

- `ok`, `fail`, `attempt`, `failWithValue`, `failTagged`, `of`, `defer`, `retry`, `retryDefer`, `retrier`, `bracket`
- `combine`, `combineAll`
- `mapItems`, `mapAll`, `mapCollectErrors`

## Branch and metadata operations

- `isOk`, `isFail`, `value`, `error`, `meta`, `withMeta`
- `tapMeta`, `mapMeta`, `mergeMeta`
- `tap`, `onSuccess`, `inspect`, `onFailure`, `inspectError`

## Transform/chaining

- `map`, `mapError`, `andThen`, `orElse`, `ensure`, `then`, `flatMap`, `thenUnsafe`
- `otherwise`, `catchException`, `matchError`, `catchError`, `recover`

## Completion/unwrapping/output

- `match`, `matchException`
- `unwrap`, `unwrapOr`, `unwrapOrElse`, `getOrThrow`, `throwIfFail`
- `toArray`, `toDebugArray`, `toJson`, `toXml`, `toResponse`

## Hard constraints

- Do not invent APIs.
- Do not use internal helper classes directly.
- Keep boundary completion explicit.

## V2 batch helper

- `ResultBatch::firstFailure`, `allErrors`, `mapFirstFailure`, `mapAllErrors`
