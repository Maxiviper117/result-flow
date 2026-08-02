# Failure Handling Reference

Use when shaping failure behavior and recovery strategy.

## Decision table

| Need | Method |
|---|---|
| Conditional failure mapping/recovery | `otherwise` |
| Throwable class-based handling | `catchException` |
| ResultError class-based finalization | `matchError` |
| ResultError class-based recovery | `catchError` |
| Always recover to success | `recover` |
| Convert failure to exception at boundary | `throwIfFail` |

## Guidance

- Keep one stable failure schema for consumers.
- Preserve metadata when mapping failures.
- Use `matchError` and `catchError` for class-based `ResultError` handling.
- Match structured errors by class, not by string code.
- Place `throwIfFail` at boundaries, not deep in domain logic.
- Expect `TFailure|Throwable` widening from `of`, `defer`, `retryDefer`, and batch helpers until normalized.
- `matchError` callbacks may be `()`, `($error)`, or `($error, $meta)` depending on what you need.
- `catchError` handlers/fallback may return plain values (auto-wrapped) or full `Result` instances.

## Anti-patterns

- Unconditional recovery hiding critical failures.
- Mixing incompatible error shapes without normalization.

## Example shape

```php
$result = callService()
    ->catchException([...])
    ->otherwise(fn ($e, $meta) => normalizeError($e, $meta));
```
