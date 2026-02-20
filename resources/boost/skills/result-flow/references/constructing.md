# Constructing Reference

Use when choosing entrypoint and aggregation semantics.

## Decision table

| Need | Method |
|---|---|
| Explicit success/failure branch | `ok` / `fail` |
| Preserve failed input | `failWithValue` |
| Wrap throwing callback | `of` |
| Callback may return value or `Result` | `defer` |
| Aggregate existing `Result[]` fail-fast | `combine` |
| Aggregate existing `Result[]` collect-all | `combineAll` |

## Guidance

- Initialize metadata early (`request_id`, `operation`).
- Prefer `defer` over `of` when callback can already return `Result`.
- Choose fail-fast vs collect-all based on consumer requirements.

## Anti-patterns

- Using `combine` when full error set is required.
- Dropping metadata at flow start.

## Example shape

```php
$result = Result::defer(fn () => $input)
    ->then(fn (array $payload) => validate($payload));
```
