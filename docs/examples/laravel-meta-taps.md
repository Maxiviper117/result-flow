---
title: Laravel metadata + taps example
---

# Laravel metadata + taps example

This example shows how to enrich metadata and emit logs/metrics without changing the pipeline.

```php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Maxiviper117\ResultFlow\Result;

final class BillingService
{
    public function charge(array $payload): Result
    {
        return Result::ok($payload, ['request_id' => $payload['request_id'] ?? null])
            ->mergeMeta(['started_at' => microtime(true)])
            ->tapMeta(fn ($meta) => Log::debug('billing.meta', $meta))
            ->then(fn ($data, $meta) => $this->callGateway($data, $meta))
            ->onSuccess(fn ($value, $meta) => Log::info('billing.ok', $meta))
            ->onFailure(fn ($error, $meta) => Log::warning('billing.fail', ['error' => $error] + $meta));
    }

    private function callGateway(array $data, array $meta): Result
    {
        // ... call provider
        return Result::ok(['charged' => true], $meta);
    }
}
```

Notes:
- `mergeMeta()` adds context without losing existing metadata.
- `tapMeta()` observes metadata without mutation.
- `onSuccess()` / `onFailure()` are ideal for metrics and logging.

## Result functions used

- `ok()`, `mergeMeta()`, `tapMeta()`, `then()`, `onSuccess()`, `onFailure()`
