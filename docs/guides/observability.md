---
title: Observability
---

# Observability

Metadata and taps let you inspect a flow without changing it.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok($input, ['request_id' => $requestId])
    ->mergeMeta(['operation' => 'user.normalize'])
    ->tapMeta(fn (array $meta) => logger()->debug('result-meta', $meta))
    ->onFailure(fn ($error, array $meta) => logger()->warning('result-failed', compact('error', 'meta')));

logger()->info('safe-debug', $result->toDebugArray());
```

## What to use

- `mergeMeta(...)` to add keys.
- `mapMeta(...)` to replace metadata.
- `tapMeta(...)` to inspect metadata.
- `tap(...)`, `onSuccess(...)`, `inspect(...)`, `onFailure(...)`, and `inspectError(...)` for side effects.
- `toDebugArray(...)` for log-safe output.

## Important detail

When a pipeline step throws, the chain records a `failed_step` metadata value with the best available step name.

## Related pages

- [Metadata](/concepts/metadata)
- [Metadata and debugging reference](/reference/metadata-debugging)
