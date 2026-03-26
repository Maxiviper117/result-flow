{{-- ResultFlow package guidelines for Laravel Boost (downstream app usage) --}}

# ResultFlow Core Guidelines

## Audience and intent

- These guidelines are for agents generating code in Laravel applications that consume `maxiviper117/result-flow`.
- Focus on app-level usage of public `Result` APIs.
- Do not include package-maintainer workflow/tooling guidance in generated app code.

## When to use ResultFlow

- Use `Maxiviper117\ResultFlow\Result` when failure is expected and should be handled explicitly in normal control flow.
- Prefer ResultFlow for multi-step workflows (validation, persistence, side effects, response mapping).
- Use the kitchen-sink docs for a full method tour when you need to compare related APIs; the pages use a consistent quick-map + method-by-method structure, and the reference docs stay best for exact signatures or edge behavior.
- Keep exception throwing for truly exceptional conditions; use `Result::fail(...)` for expected domain/application failures.
- Use `Result::failWithValue(...)` when the input that triggered failure should stay visible in metadata.
- Use `Result::of(...)` when a callback returns a plain value on success and throws on failure; it always wraps the callback return value as success.
- Use `Result::defer(...)` when a callback may return either a plain value or another `Result`; it preserves returned `Result` instances instead of wrapping them.
- Use `Result::retryDefer(...)` or `Result::retrier()` for transient retries, not for validation or deterministic business rules.
- Use `Result::combineAll(...)` when aggregating existing `Result[]` and you need every failure preserved; if any input fails, it returns only the collected failures and no success values.

## Canonical flow shape

- Start with `Result::ok($input, $meta)`, `Result::fail($error, $meta)`, `Result::of(fn () => ...)`, `Result::defer(fn () => ...)`, or `Result::bracket(...)` when resource safety is required.
- Compose steps with `->ensure(...)`, `->then(...)`, and `->otherwise(...)`.
- Use `->thenUnsafe(...)` only when exception bubbling is the desired boundary behavior, such as transaction rollback.
- Use `->recover(...)` only when you intentionally convert failure into success.
- End branches explicitly with either:
  - `->toResponse()` in Laravel HTTP flows, or
  - `->match(onSuccess: ..., onFailure: ...)` for non-HTTP/custom boundaries.
- Use `->matchException(...)` when the boundary needs Throwable-class-specific handling.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok($request->validated(), ['request_id' => (string) $request->header('X-Request-Id')])
    ->ensure(fn (array $input) => isset($input['email']), 'Email is required')
    ->then(fn (array $input) => $userService->create($input))
    ->otherwise(fn ($error, array $meta) => Result::fail([
        'message' => (string) $error,
        'request_id' => $meta['request_id'] ?? null,
    ], $meta));

return $result->toResponse();
```

## Metadata discipline

- Metadata is part of the contract. Treat it as `array<string,mixed>`.
- Preserve metadata across steps; when transforming failure, forward existing `$meta`.
- Use stable keys like `request_id`, `trace_id`, `operation`, and `context`.
- If a pipeline step throws, prefer the built-in `failed_step` metadata over ad-hoc duplicate step labels.
- `mapMeta()` and `mergeMeta()` can inspect the current success value when the result is `Ok`.
- `Result::retry(...)` and `Result::retryDefer(...)` can expose retry attempt metadata when the builder enables it.
- Do not silently discard metadata inside `then`, `otherwise`, or `recover` handlers.

## Laravel response and transaction patterns

- For HTTP endpoints, return `Result` from service/action layers and convert once at the edge with `->toResponse()`.
- Keep `toResponse()` payloads JSON-encodable; the non-Laravel fallback serializes to a JSON string body and can fail on invalid encoding.
- For transactions that must rollback on `Result` failure, call `->throwIfFail()` inside the transaction closure.
- Map low-level errors to user-safe structures in `otherwise(...)`, while keeping diagnostic details in metadata.
- Use `Result::retryDefer(...)` for transient operations that may return value/Result or throw.
- Use `Result::bracket(...)` for acquire/use/release flows where cleanup must always run.
- If you serialize to XML, remember tag names are normalized for XML safety; treat XML output as a transport format, not a lossless mirror of arbitrary array keys.
- Use `toDebugArray()` for logs and diagnostics instead of `toArray()` when payloads may contain secrets or large strings.

## Error payload conventions

- Use predictable error payloads (`string` or structured array) and be consistent per workflow.
- If using arrays, include at least a user-facing `message` key and forward correlation keys from metadata.
- Avoid mixing many unrelated error shapes in the same chain unless the call site normalizes them.

## Type-safety defaults

- Prefer typed callback arguments and returns whenever concrete types are known.
- Return `Result` from chain handlers when behavior is branch-aware; avoid unnecessary `mixed` widening.
- Use only documented public `Result` methods.

## Anti-patterns to avoid

- Do not model expected validation/business failures only with thrown exceptions.
- Do not drop metadata when converting one failure shape to another.
- Do not leave a flow without explicit branch completion (`match`, `toResponse`, `unwrap*`, etc.) at the app boundary.
