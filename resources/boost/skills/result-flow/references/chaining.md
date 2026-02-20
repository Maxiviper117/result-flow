# Chaining Reference

Use when composing success-path transforms and dependent steps.

## Decision table

| Need | Method |
|---|---|
| Pure success transform | `map` |
| Guard success with predicate | `ensure` |
| Chain step returning value/`Result` with exception capture | `then` / `flatMap` |
| Intentionally bubble exceptions | `thenUnsafe` |
| Normalize failure payload | `mapError` |

## Guidance

- Run `ensure` early for cheaper failure.
- Use `then` for branch-aware step composition.
- Use `thenUnsafe` only with explicit exception boundary intent.

## Anti-patterns

- Returning `Result` from `map` callbacks.
- Using `thenUnsafe` by default.

## Example shape

```php
$result = Result::ok($dto)
    ->ensure(fn ($v) => isValid($v), 'Invalid input')
    ->then(fn ($v) => persist($v));
```
