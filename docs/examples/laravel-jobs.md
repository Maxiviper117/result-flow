---
title: Laravel jobs + queue example
---

# Laravel jobs + queue example

This example shows how to use Result Flow inside a queued job. The job reports success or failure and logs a sanitized debug payload.

```php
namespace App\Jobs;

use App\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maxiviper117\ResultFlow\Result;

final class ImportCustomersJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $path) {}

    public function handle(ImportService $imports): void
    {
        $result = Result::ok(['path' => $this->path])
            ->then(fn ($data) => $imports->loadCsv($data['path']))
            ->then(fn ($rows) => $imports->upsertRows($rows))
            ->otherwise(fn ($error, $meta) => Result::fail([
                'message' => (string) $error,
            ], $meta));

        Log::info('imports.result', $result->toDebugArray());

        // Optionally fail the job on error
        $result->throwIfFail();
    }
}
```

Notes:
- `throwIfFail()` will bubble exceptions for failed jobs if the error is a Throwable.
- `toDebugArray()` keeps logs safe by redacting sensitive keys.

## Result functions used

- `ok()`, `then()`, `otherwise()`, `fail()`, `toDebugArray()`, `throwIfFail()`
