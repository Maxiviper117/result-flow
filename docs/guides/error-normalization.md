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
- `of(...)` is the right entry point here when `$gateway->send(...)` returns a plain payload on success and throws on failure

If the upstream gateway may already return `Result::ok(...)` or `Result::fail(...)`, switch the entry point to `Result::defer(...)` so the upstream result is preserved instead of wrapped as a success value.

## Related pages

- [Failure handling](/concepts/failure-handling)
- [Failure handling reference](/reference/failure-handling)
