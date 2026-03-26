---
title: Batch Strategy
---

# Batch Strategy

Choose the batch primitive based on the shape of the problem, not on convenience.

| Need | Use |
| --- | --- |
| Per-item status | `mapItems(...)` |
| Fail fast on the first bad item | `mapAll(...)` |
| Return every failure | `mapCollectErrors(...)` |
| Aggregate existing `Result[]` fail fast | `combine(...)` |
| Aggregate existing `Result[]` and keep all failures | `combineAll(...)` |

## Rule of thumb

- validation screens usually want collect-all
- write pipelines usually want fail-fast
- keyed UI errors should stay keyed
- existing `Result[]` should use `combine` or `combineAll`

## Related pages

- [Batch processing](/concepts/batch-processing)
- [Batch processing reference](/reference/batch-processing)
