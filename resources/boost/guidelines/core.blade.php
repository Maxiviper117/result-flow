{{-- ResultFlow package guidelines for Laravel Boost (downstream app usage) --}}

# ResultFlow Core Guidelines

## Audience and intent

- These guidelines are for agents generating code in Laravel applications that consume `maxiviper117/result-flow`.
- Focus on app-level usage of public `Result` APIs.
- Do not include package-maintainer workflow/tooling guidance in generated app code.

## When to use ResultFlow

- Use `Maxiviper117\ResultFlow\Result` when failure is expected and should be handled explicitly in normal control flow.
- Prefer ResultFlow for multi-step workflows (validation, persistence, side effects, response mapping).
- Keep exception throwing for truly exceptional conditions; use `Result::fail(...)` for expected domain/application failures.

## Canonical flow shape

- Start with `Result::ok($input, $meta)`, `Result::fail($error, $meta)`, `Result::defer(fn () => ...)`, or `Result::bracket(...)` when resource safety is required.
- Compose steps with `->ensure(...)`, `->then(...)`, and `->otherwise(...)`.
- Use `->recover(...)` only when you intentionally convert failure into success.
- End branches explicitly with either:
  - `->toResponse()` in Laravel HTTP flows, or
  - `->match(onSuccess: ..., onFailure: ...)` for non-HTTP/custom boundaries.

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
- Do not silently discard metadata inside `then`, `otherwise`, or `recover` handlers.

## Laravel response and transaction patterns

- For HTTP endpoints, return `Result` from service/action layers and convert once at the edge with `->toResponse()`.
- For transactions that must rollback on `Result` failure, call `->throwIfFail()` inside the transaction closure.
- Map low-level errors to user-safe structures in `otherwise(...)`, while keeping diagnostic details in metadata.
- Use `Result::retryDefer(...)` for transient operations that may return value/Result or throw.
- Use `Result::bracket(...)` for acquire/use/release flows where cleanup must always run.

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
