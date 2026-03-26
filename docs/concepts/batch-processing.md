---
title: Batch Processing
---

# Batch Processing

Batch tools make the difference between item-level status and whole-collection status explicit.

```php
use Maxiviper117\ResultFlow\Result;

$results = Result::mapItems($rows, fn (array $row, string $key) => validateRow($row, $key));
```

## The batch families

- `mapItems(...)` returns one `Result` per input item.
- `mapAll(...)` fails fast on the first failure and returns keyed success values.
- `mapCollectErrors(...)` processes everything and returns keyed failures.
- `combine(...)` and `combineAll(...)` aggregate already-built `Result` values.

## What to remember

- `mapAll(...)` and `combine(...)` are fail-fast.
- `mapCollectErrors(...)` and `combineAll(...)` collect all failures.
- keys are preserved where the API says they are preserved.

## Related pages

- [Batch processing reference](/reference/batch-processing)
- [Batch strategy guide](/guides/batch-strategy)
