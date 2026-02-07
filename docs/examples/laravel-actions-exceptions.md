---
title: Laravel Actions Exceptions
---

# Laravel Actions Exceptions

## Scenario

Normalize thrown exceptions from action classes.

## Example

```php
$result = Result::of(fn () => (new RiskyAction)->execute($dto))
    ->catchException([
        RuntimeException::class => fn (RuntimeException $e) => Result::fail("runtime: {$e->getMessage()}"),
    ], fallback: fn ($error) => Result::fail("unhandled: {$error}"));
```

## Expected behavior

- Exception classes can be handled selectively.
- Unmatched failures can still be routed through fallback.

## Related pages

- [Error Handling](/result/error-handling)
- [API Reference](/api)
