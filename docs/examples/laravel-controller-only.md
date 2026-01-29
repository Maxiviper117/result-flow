---
title: Laravel controller-only example
---

# Laravel controller-only example

This example keeps everything in the controller (no service class) for teams that prefer thin layers. It demonstrates `then()`, `otherwise()`, `ensure()`, and `toResponse()` in one place.

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maxiviper117\ResultFlow\Result;

final class CheckoutController
{
    public function store(Request $request)
    {
        $result = Result::ok($request->all(), ['source' => 'checkout'])
            ->ensure(fn ($data) => isset($data['items']) && count($data['items']) > 0, 'No items')
            ->then(fn ($data, $meta) => $this->calculateTotals($data, $meta))
            ->then(fn ($data, $meta) => $this->persistOrder($data, $meta))
            ->otherwise(fn ($error, $meta) => Result::fail([
                'message' => (string) $error,
                'meta' => $meta,
            ], $meta));

        return $result->toResponse();
    }

    private function calculateTotals(array $data, array $meta): array
    {
        $data['total'] = array_sum(array_map(fn ($i) => $i['price'] * $i['qty'], $data['items']));

        return $data;
    }

    private function persistOrder(array $data, array $meta): Result
    {
        // ... write order to DB
        return Result::ok(['id' => 123, 'total' => $data['total']], $meta);
    }
}
```

## Result functions used

- `ok()`, `ensure()`, `then()`, `otherwise()`, `fail()`, `toResponse()`
