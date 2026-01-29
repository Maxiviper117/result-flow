---
title: Laravel action exception handling example
---

# Laravel action exception handling example

This example uses an action class with `catchException()` and `matchException()` for error classification.

```php
namespace App\Actions;

use Maxiviper117\ResultFlow\Result;
use RuntimeException;
use InvalidArgumentException;

final class ImportCatalog
{
    public function __invoke(string $path): Result
    {
        return Result::of(fn () => $this->load($path))
            ->catchException([
                InvalidArgumentException::class => fn ($e, $meta) => Result::fail('invalid-input', $meta),
                RuntimeException::class => fn ($e, $meta) => Result::fail('system-failure', $meta),
            ]);
    }

    private function load(string $path): array
    {
        // may throw
        return ['items' => 10];
    }
}
```

```php
$result = (new ImportCatalog())('catalog.csv')->matchException(
    [
        RuntimeException::class => fn ($e, $meta) => 'retry later',
    ],
    onSuccess: fn ($payload, $meta) => 'ok',
    onUnhandled: fn ($error, $meta) => 'failed',
);
```

## Result functions used

- `of()`, `catchException()`, `fail()`, `matchException()`
