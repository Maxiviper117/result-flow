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
- `tapMeta(...)` observes metadata without changing the result. On success the callback may also receive the value as a second argument.
- `mapMeta(...)` replaces the metadata map.
- `mergeMeta(...)` adds or overwrites keys.

On `Ok`, `mapMeta(...)` and `mergeMeta(...)` may accept callbacks that also receive the current value as a second argument (callbacks receive metadata first).

Note: When a callable accepts two parameters the library now passes the value as the second argument for `Ok` results and `null` for `Fail` results. Prefer an optional or nullable second parameter (e.g. `fn(array $meta, $value = null)` or `fn(array $meta, ?MyType $value = null)`) to handle both branches safely and avoid static analysis warnings.

## A useful rule

If a step changes the result shape, decide whether metadata should be preserved or updated at the same time. Do not silently drop it.

## Related pages

- [Metadata and debugging reference](/reference/metadata-debugging)
- [Observability guide](/guides/observability)
