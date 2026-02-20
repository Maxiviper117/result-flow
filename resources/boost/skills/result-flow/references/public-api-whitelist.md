# Public API Whitelist Reference

Use this list to ensure generated code sticks to documented public `Result` APIs.

## Static constructors/utilities

- `ok`, `fail`, `failWithValue`, `of`, `defer`, `retry`, `retryDefer`, `retrier`, `bracket`
- `combine`, `combineAll`
- `mapItems`, `mapAll`, `mapCollectErrors`

## Branch and metadata operations

- `isOk`, `isFail`, `value`, `error`, `meta`
- `tapMeta`, `mapMeta`, `mergeMeta`
- `tap`, `onSuccess`, `inspect`, `onFailure`, `inspectError`

## Transform/chaining

- `map`, `mapError`, `ensure`, `then`, `flatMap`, `thenUnsafe`
- `otherwise`, `catchException`, `recover`

## Completion/unwrapping/output

- `match`, `matchException`
- `unwrap`, `unwrapOr`, `unwrapOrElse`, `getOrThrow`, `throwIfFail`
- `toArray`, `toDebugArray`, `toJson`, `toXml`, `toResponse`

## Hard constraints

- Do not invent APIs.
- Do not use internal helper classes directly.
- Keep boundary completion explicit.
