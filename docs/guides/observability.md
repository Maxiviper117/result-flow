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
 
Note: If your metadata callback accepts two parameters, the library will pass the flow value as the second argument for `Ok` results and `null` for `Fail` results. Use an optional/nullable second parameter to handle both branches safely (for example `fn(array $meta, $value = null)`).
- `tap(...)`, `onSuccess(...)`, `inspect(...)`, `onFailure(...)`, and `inspectError(...)` for side effects.
- `toDebugArray(...)` for log-safe output.

## Important detail

When a pipeline step throws, the chain records a `failed_step` metadata value with the best available step name.

## Related pages

- [Metadata](/concepts/metadata)
- [Metadata and debugging reference](/reference/metadata-debugging)
