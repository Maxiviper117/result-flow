# ResultFlow Laravel Workflow Skill

## Mission and scope

Generate Laravel-oriented flows using `Maxiviper117\ResultFlow\Result` with explicit success/failure branches, metadata propagation, and predictable HTTP/domain output handling.

Use this skill for controllers, actions, services, jobs, and transaction workflows where failure handling must stay explicit and type-safe.

## Inputs expected from user/codebase

- The workflow entrypoint (controller method, action class, service method, job handle).
- Input DTO/array/request shape and required validations.
- Expected success output shape and failure output shape.
- Any required metadata keys (`request_id`, `trace_id`, `operation`, etc.).
- Whether flow ends in HTTP response (`toResponse`) or custom branch handling (`match`).

## Generation rules (API whitelist)

Only use public methods present on `src/Result.php`:

- Static constructors/utilities:
  - `ok`, `fail`, `failWithValue`, `of`, `retry`, `retrier`
  - `combine`, `combineAll`
  - `mapItems`, `mapAll`, `mapCollectErrors`
- Branch and metadata operations:
  - `isOk`, `isFail`, `value`, `error`, `meta`
  - `tapMeta`, `mapMeta`, `mergeMeta`
  - `tap`, `onSuccess`, `inspect`, `onFailure`, `inspectError`
- Transform/chaining:
  - `map`, `mapError`, `ensure`, `then`, `flatMap`, `thenUnsafe`
  - `otherwise`, `catchException`, `recover`
- Completion/unwrapping/output:
  - `match`, `matchException`
  - `unwrap`, `unwrapOr`, `unwrapOrElse`, `getOrThrow`, `throwIfFail`
  - `toArray`, `toDebugArray`, `toJson`, `toXml`, `toResponse`

Hard constraints:

- Do not invent or reference methods/classes that are not in this package or the host app.
- Preserve metadata (`array<string,mixed>`) across flow boundaries.
- End each flow with explicit branch completion (`match` or `toResponse`) unless returning `Result` intentionally to caller.
- Prefer typed callback signatures when concrete types are known.
- Keep generated code compatible with project automation (`composer rector-dry`, `composer analyse`, `composer test`).

## Output checklist before returning

- Branch coverage:
  - Success path is explicit.
  - Failure path is explicit.
- Metadata:
  - Initial metadata is provided (or intentionally empty).
  - Metadata survives across `then`/`otherwise` transformations.
- Types:
  - Callback parameter and return types are concrete where possible.
  - No unnecessary widening to `mixed`.
- Failure mapping:
  - Errors are normalized to a stable shape for the consumer.
- Transactions (when relevant):
  - `throwIfFail()` is used where rollback is required.

## Example prompts and outcomes

### Prompt
Create a controller action that validates request input, creates a user, maps domain failures, and returns a response.

### Outcome shape

- `Result::ok($request->validated(), $meta)`
- `ensure(...)` for required invariants
- `then(...)` for service call
- `otherwise(...)` for failure mapping with metadata passthrough
- `toResponse()` at HTTP boundary

### Prompt
Create an order checkout flow in a DB transaction that must rollback on Result failures.

### Outcome shape

- `DB::transaction(fn () => Result::ok(...)->thenUnsafe(...)->throwIfFail()->thenUnsafe(...))`
- Domain-safe error mapping after transaction
- Explicit completion in caller (`match` or `toResponse`)
