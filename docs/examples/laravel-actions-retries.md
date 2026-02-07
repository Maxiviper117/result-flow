---
title: Laravel Actions Retries
---

# Laravel Actions Retries

## Scenario

Retry an action that can transiently fail.

## Example

```php
$result = Result::retrier()
    ->maxAttempts(3)
    ->delay(100)
    ->attempt(fn () => (new SyncExternalAction)->execute($dto));
```

## Expected behavior

- Retries are centralized in retrier config.
- Action remains focused on business logic.

## Related pages

- [Retrying](/result/retrying)
- [Laravel Actions](/examples/laravel-actions)
