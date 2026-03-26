---
title: Resource Cleanup
---

# Resource Cleanup

Goal: acquire a resource, use it, and release it even when the use step fails.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::bracket(
    acquire: fn () => fopen($path, 'r'),
    use: fn ($handle) => fread($handle, 100),
    release: fn ($handle) => fclose($handle),
);
```

## Why this pattern works

- release is attempted after acquisition succeeds
- use failures are preserved
- release failures are attached to metadata when use also fails

## Variation

If you only need exception-safe execution, consider `thenUnsafe(...)` inside an exception-aware boundary instead.
