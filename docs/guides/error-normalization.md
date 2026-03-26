---
title: Error Normalization
---

# Error Normalization

Normalize errors where they enter the flow, not after they have already spread into the chain.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::of(fn () => $gateway->send($payload))
    ->catchException([
        RuntimeException::class => fn (RuntimeException $e, array $meta) => Result::fail([
            'code' => 'UPSTREAM_TIMEOUT',
            'message' => $e->getMessage(),
        ], $meta),
    ])
    ->otherwise(fn (array $error, array $meta) => Result::fail([
        ...$error,
        'operation' => $meta['operation'] ?? 'unknown',
    ], $meta));
```

## Why this pattern works

- it keeps one stable error schema
- it preserves metadata
- it avoids mixing raw exceptions with structured failures

## Related pages

- [Failure handling](/concepts/failure-handling)
- [Failure handling reference](/reference/failure-handling)
