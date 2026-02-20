# ResultFlow Laravel Workflow Skill

## Mission and scope

Generate Laravel-oriented flows using `Maxiviper117\ResultFlow\Result` with explicit success/failure branches, metadata propagation, and predictable HTTP/domain output handling.

Use this skill for controllers, actions, services, jobs, and transaction workflows in downstream Laravel applications that consume this package.

## Inputs expected from user/codebase

- The workflow entrypoint (controller method, action class, service method, job handle).
- Input DTO/array/request shape and required validations.
- Expected success output shape and failure output shape.
- Required metadata keys (`request_id`, `trace_id`, `operation`, etc.).
- Whether flow ends in HTTP response (`toResponse`) or custom branch handling (`match`).

## Generation rules (public API whitelist)

Only use documented public `Result` methods:

- Static constructors/utilities:
  - `ok`, `fail`, `failWithValue`, `of`, `defer`, `retry`, `retryDefer`, `retrier`, `bracket`
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

- Do not invent methods/classes not in this package or the host app.
- Do not depend on internal package helper classes directly.
- Preserve metadata (`array<string,mixed>`) across flow boundaries.
- End each flow with explicit branch completion (`match` or `toResponse`) unless returning `Result` intentionally.
- Prefer typed callback signatures when concrete types are known.
- Follow the host application's coding standards, tests, and CI requirements.

## Output checklist before returning

- Success path is explicit.
- Failure path is explicit.
- Metadata is initialized (or intentionally empty) and preserved.
- Errors are normalized to a stable shape for consumer boundaries.
- Transactions that require rollback use `throwIfFail()` at the right boundary.

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

### Prompt
Start a service workflow from a lazy/computed first step and keep failure explicit.

### Outcome shape

- `Result::defer(fn () => $service->fetch($id))`
- `then(...)` for dependent steps
- `otherwise(...)` for error normalization with metadata passthrough

### Prompt
Retry transient workflow setup that may throw or return `Result`.

### Outcome shape

- `Result::retryDefer(3, fn () => $gateway->send($payload), delay: 100, exponential: true)`
- `otherwise(...)` to map terminal failure for caller

### Prompt
Use a temporary resource with guaranteed cleanup.

### Outcome shape

- `Result::bracket(acquire: ..., use: ..., release: ...)`
- `use` branch returns domain `Result`
- Cleanup errors handled as explicit Result failure semantics
