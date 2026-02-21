# Batch Processing Reference

Use for collection workflows and aggregation semantics.

## Decision table

| Input | Need | Method |
|---|---|---|
| Raw items | Per-item `Result` map | `mapItems` |
| Raw items | Fail-fast aggregate | `mapAll` |
| Raw items | Collect-all keyed errors | `mapCollectErrors` |
| Existing `Result[]` | Fail-fast aggregate | `combine` |
| Existing `Result[]` | Collect-all failures | `combineAll` |

## Guidance

- Preserve keys for UI/form diagnostics.
- Choose collect-all when consumer needs complete error visibility.

## Anti-patterns

- Fail-fast in validation scenarios requiring full error reporting.
- Mixing raw items and `Result[]` paths in one function.

## Example shape

```php
$result = Result::mapCollectErrors($rows, fn ($row, $key) => validateRow($row, $key));
```
