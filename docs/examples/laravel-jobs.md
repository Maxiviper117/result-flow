---
title: Laravel Jobs and Queues
---

# Laravel Jobs and Queues

## Scenario

Use Result Flow inside queued jobs for explicit retry/failure behavior.

## Example

```php
public function handle(): void
{
    $result = Result::of(fn () => $this->service->process($this->payload))
        ->otherwise(function ($error) {
            report($error);
            return Result::fail($error);
        });

    $result->throwIfFail();
}
```

## Expected behavior

- Job failure path is explicit.
- Throwing at the end integrates with queue retry/dead-letter behavior.

## Related pages

- [Error Handling](/result/error-handling)
- [Retrying](/result/retrying)
