---
title: Finalization Boundaries
---

# Finalization Boundaries

A flow becomes useful only when it ends somewhere specific.

```php
use Maxiviper117\ResultFlow\Result;

$payload = $result->match(
    onSuccess: fn ($value, array $meta) => ['ok' => true, 'data' => $value, 'meta' => $meta],
    onFailure: fn ($error, array $meta) => ['ok' => false, 'error' => $error, 'meta' => $meta],
);
```

## Finalization tools

- `match(...)` for branch-aware outputs.
- `matchException(...)` for Throwable-aware outputs.
- `unwrap*` when you need a plain value and have a deliberate fallback or throw policy.
- `toJson(...)`, `toXml(...)`, and `toResponse(...)` when the boundary is a transport format.

## Why boundaries matter

If you finalize too early, you lose branch control and metadata.

If you finalize too late, the calling layer cannot consume the result cleanly.

The rule is simple: keep composing until the edge, then finish once.

## Related pages

- [Failure handling](/concepts/failure-handling)
- [Boundary reference](/reference/boundaries)
- [Metadata and debugging reference](/reference/metadata-debugging)
