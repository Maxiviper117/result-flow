---
title: Laravel workflow example
---

# Laravel workflow example

This example uses common Laravel patterns: a controller delegates to a service class, the service returns a `Result`, and the controller returns `toResponse()`.

## Scenario

Create an order:
- Validate input (Form Request)
- Persist order + line items
- Charge payment provider
- Return success or failure as JSON

## Form Request (validation)

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'total' => ['required', 'numeric', 'min:0.01'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku' => ['required', 'string'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }
}
```

## Service (business logic)

```php
namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Maxiviper117\ResultFlow\Result;

final class OrderService
{
    public function create(array $data): Result
    {
        return Result::ok($data, ['source' => 'orders.create'])
            ->then(fn ($payload, $meta) => $this->persist($payload, $meta))
            ->then(fn ($order, $meta) => $this->charge($order, $meta));
    }

    private function persist(array $data, array $meta): Result
    {
        return Result::of(function () use ($data, $meta) {
            return DB::transaction(function () use ($data, $meta) {
                $order = Order::create([
                    'email' => $data['email'],
                    'total' => $data['total'],
                ]);

                foreach ($data['items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'sku' => $item['sku'],
                        'qty' => $item['qty'],
                    ]);
                }

                return Result::ok($order, $meta + ['order_id' => $order->id]);
            });
        });
    }

    private function charge(Order $order, array $meta): Result
    {
        return Result::of(function () use ($order, $meta) {
            $receipt = app('payments')->charge($order->total, $order->email);

            return Result::ok([
                'order_id' => $order->id,
                'receipt' => $receipt,
            ], $meta);
        });
    }
}
```

## Controller

```php
namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Maxiviper117\ResultFlow\Result;

final class OrderController
{
    public function store(StoreOrderRequest $request, OrderService $orders)
    {
        $result = $orders->create($request->validated())
            ->otherwise(function ($error, $meta) {
                // Normalize errors for the API response
                return Result::fail([
                    'message' => (string) $error,
                    'meta' => $meta,
                ], $meta);
            });

        return $result->toResponse();
    }
}
```

Notes:
- Form Request keeps validation in the HTTP layer.
- The service returns `Result` and never throws directly.
- `Result::of()` converts exceptions into failures without try/catch in the controller.
- `toResponse()` returns a JSON response (status 200/400) in Laravel.

## Result functions used

- `ok()`, `then()`, `ensure()`, `of()`, `otherwise()`, `fail()`, `toResponse()`
