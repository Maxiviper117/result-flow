---
title: Laravel retries around external calls
---

# Laravel retries around external calls

This example shows how to use the retrier to wrap flaky external calls (HTTP, queues, third-party APIs).

## Service

```php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Maxiviper117\ResultFlow\Result;

final class ShippingService
{
    public function createLabel(array $payload): Result
    {
        return Result::retrier()
            ->maxAttempts(3)
            ->delay(200)
            ->exponential()
            ->when(fn ($error, $attempt) => $this->shouldRetry($error))
            ->onRetry(function ($attempt, $error, $wait) {
                Log::warning('shipping.retry', [
                    'attempt' => $attempt,
                    'error' => (string) $error,
                    'wait_ms' => $wait,
                ]);
            })
            ->attempt(fn () => $this->callCarrier($payload));
    }

    private function shouldRetry(mixed $error): bool
    {
        return $error instanceof \RuntimeException;
    }

    private function callCarrier(array $payload): array
    {
        // throw on transport error or return array payload
        return ['label_id' => 'abc123'];
    }
}
```

Notes:
- `attempt()` accepts raw values or Results. Raw values are wrapped as `Result::ok`.
- Exceptions thrown by `callCarrier()` become failures and are eligible for retries.
- Keep the retry predicate narrow to avoid retrying validation errors.

## Result functions used

- `retrier()`, `maxAttempts()`, `delay()`, `exponential()`, `when()`, `onRetry()`, `attempt()`
