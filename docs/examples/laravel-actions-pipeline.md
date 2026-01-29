---
title: Laravel action pipeline example
---

# Laravel action pipeline example

This example shows an action class that composes multiple steps using `then()` and handles failures in one place.

```php
namespace App\Actions;

use Maxiviper117\ResultFlow\Result;

final class CreateInvoice
{
    public function __invoke(array $data): Result
    {
        return Result::ok($data)
            ->then(fn ($payload, $meta) => $this->validate($payload, $meta))
            ->then(fn ($payload, $meta) => $this->persist($payload, $meta))
            ->otherwise(fn ($error, $meta) => Result::fail(['message' => (string) $error], $meta));
    }

    private function validate(array $data, array $meta): Result
    {
        return empty($data['amount']) ? Result::fail('Missing amount', $meta) : Result::ok($data, $meta);
    }

    private function persist(array $data, array $meta): Result
    {
        // ... store invoice
        return Result::ok(['id' => 10], $meta);
    }
}
```

## Result functions used

- `ok()`, `then()`, `otherwise()`, `fail()`
