# Boundaries Reference

Use when finalizing a `Result` into app-facing output.

## Decision table

| Boundary need | Method |
|---|---|
| Branch-aware output object | `match` |
| Throwable-class branch handling | `matchException` |
| Plain value fallback | `unwrapOr` / `unwrapOrElse` |
| Throw custom exception | `getOrThrow` |
| Throw on failure with default conversion | `throwIfFail` |
| HTTP edge conversion | `toResponse` |

## Guidance

- Use one boundary style per function unless bridging layers.
- Normalize errors before exposing to transport/UI boundaries.
- Keep `toJson` and non-Laravel `toResponse` payloads JSON-encodable; invalid encoding should be treated as a boundary error.
- `toXml()` normalizes invalid element names; do not depend on raw user-provided keys becoming literal XML tag names.

## Anti-patterns

- Calling `toResponse` in deep domain logic.
- Using `unwrap` where failure is expected business behavior.

## Example shape

```php
return $result->match(
    onSuccess: fn ($value) => [...],
    onFailure: fn ($error) => [...],
);
```
