---
title: Retries
---

# Retries

Retries are for transient failures, not for validation or deterministic business rules.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::retryDefer(
    3,
    fn () => callExternalApi($payload),
    delay: 100,
    exponential: true,
);
```

## The three entry points

- `retry(...)` for a small policy when the callback already fits the retry contract.
- `retryDefer(...)` when the callback may return a value, a `Result`, or throw.
- `retrier()` when you need a fluent builder with predicates, jitter, hooks, and attempt metadata.

## Behavior that matters

- retries are bounded
- delay can be linear or exponential
- the builder can attach `meta['retry']['attempts']`
- terminal failure is still a normal failure result

## Common mistakes

- Retrying validation errors.
- Leaving attempt counts and observability out of the design.
- Using retries instead of failure normalization.

## Related pages

- [Construction reference](/reference/construction)
- [Batch strategy guide](/guides/batch-strategy)
