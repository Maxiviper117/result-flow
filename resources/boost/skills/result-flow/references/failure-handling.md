# Failure Handling Reference

Use when shaping failure behavior and recovery strategy.

## Decision table

| Need | Method |
|---|---|
| Conditional failure mapping/recovery | `otherwise` |
| Throwable class-based handling | `catchException` |
| Structured error matching | `matchError` |
| Structured error recovery | `catchError` |
| Always recover to success | `recover` |
| Convert failure to exception at boundary | `throwIfFail` |

## Guidance

- Keep one stable failure schema for consumers.
- Preserve metadata when mapping failures.
- Use `matchError` and `catchError` for class-based `ResultError` handling.
- Match structured errors by class, not by string code.
- Place `throwIfFail` at boundaries, not deep in domain logic.

## Anti-patterns

- Unconditional recovery hiding critical failures.
- Mixing incompatible error shapes without normalization.

## Example shape

```php
$result = callService()
    ->catchException([...])
    ->otherwise(fn ($e, $meta) => normalizeError($e, $meta));
```
