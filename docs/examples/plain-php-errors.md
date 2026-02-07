---
title: Plain PHP Error Handling
---

# Plain PHP Error Handling

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::of(fn () => riskyOperation())
    ->otherwise(fn ($error) => Result::fail("domain: {$error}"))
    ->recover(fn ($error) => ['fallback' => true, 'reason' => (string) $error]);
```

Use this shape when you want deterministic recovery without throwing at the call site.

Related:
- [Error Handling](/result/error-handling)
- [Matching and Unwrapping](/result/matching-unwrapping)
