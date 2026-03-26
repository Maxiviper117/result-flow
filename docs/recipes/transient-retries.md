---
title: Transient Retries
---

# Transient Retries

Goal: retry a transient operation without turning retry policy into business logic.

```php
use Maxiviper117\ResultFlow\Result;
use RuntimeException;

$result = Result::retryDefer(
    3,
    fn () => sendWebhook($payload),
    delay: 100,
    exponential: true,
);
```

## Why this pattern works

- the callback can return a value, a `Result`, or throw
- retry budget stays explicit
- terminal failure remains a normal failure result

## Variation

Use `Result::retrier()` when you need predicates, jitter, or attempt hooks.
