---
title: Laravel action retries example
---

# Laravel action retries example

This example uses an action class and the retry builder to call a flaky dependency.

```php
namespace App\Actions;

use Illuminate\Support\Facades\Log;
use Maxiviper117\ResultFlow\Result;

final class FetchExchangeRate
{
    public function __invoke(string $currency): Result
    {
        return Result::retrier()
            ->maxAttempts(3)
            ->delay(100)
            ->exponential()
            ->onRetry(fn ($attempt, $error, $wait) => Log::warning('rate.retry', compact('attempt', 'wait')))
            ->attempt(fn () => $this->callApi($currency));
    }

    private function callApi(string $currency): array
    {
        // may throw
        return ['currency' => $currency, 'rate' => 1.1];
    }
}
```

## Result functions used

- `retrier()`, `maxAttempts()`, `delay()`, `exponential()`, `onRetry()`, `attempt()`
