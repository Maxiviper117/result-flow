---
title: Laravel Retries
---

# Laravel Retries

## Scenario

Retry a transient HTTP/API call with metadata for attempts.

## Example

```php
$result = Result::retrier()
    ->maxAttempts(4)
    ->delay(200)
    ->exponential()
    ->attachAttemptMeta()
    ->attempt(fn () => Http::post($endpoint, $payload)->throw()->json());

return $result->toResponse();
```

## Expected behavior

- Transient failures can recover within configured attempts.
- Attempt count is available under `meta()['retry']['attempts']`.

## Related pages

- [Retrying](/result/retrying)
- [Metadata and Debugging](/result/metadata-debugging)
