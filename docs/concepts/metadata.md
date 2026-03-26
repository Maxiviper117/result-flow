---
title: Metadata
---

# Metadata

Metadata travels with the branch.

```php
use Maxiviper117\ResultFlow\Result;

$value = ['id' => 1];

$result = Result::ok($value, ['request_id' => 'r-1'])
    ->mergeMeta(['operation' => 'normalize']);
```

## What metadata is for

- correlation IDs
- operation names
- failed input
- retry counts
- observability context

## How it behaves

- `meta()` returns the current map.
- `tapMeta(...)` observes metadata without changing the result.
- `mapMeta(...)` replaces the metadata map.
- `mergeMeta(...)` adds or overwrites keys.

On `Ok`, `mapMeta(...)` and `mergeMeta(...)` may accept callbacks that also receive the current value.

## A useful rule

If a step changes the result shape, decide whether metadata should be preserved or updated at the same time. Do not silently drop it.

## Related pages

- [Metadata and debugging reference](/reference/metadata-debugging)
- [Observability guide](/guides/observability)
