---
title: API Boundary Mapping
---

# API Boundary Mapping

Goal: finish a `Result` at an HTTP boundary without leaking internal control flow into the response layer.

```php
use Maxiviper117\ResultFlow\Result;

return $result->match(
    onSuccess: fn (array $value, array $meta) => response()->json([
        'ok' => true,
        'data' => $value,
        'meta' => $meta,
    ], 200),
    onFailure: fn ($error, array $meta) => response()->json([
        'ok' => false,
        'error' => $error,
        'meta' => $meta,
    ], 400),
);
```

## Why this pattern works

- the boundary is explicit
- the caller sees one response shape
- metadata stays available for tracing

## Variation

If you want the package to build the response shape for you, use [`toResponse()`](/reference/boundaries).
