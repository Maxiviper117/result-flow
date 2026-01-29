---
title: Laravel transactions + rollback
---

# Laravel transactions + rollback

This example shows how to combine `thenUnsafe()` with `throwIfFail()` to get rollback semantics in database transactions using a service class.

## Service

```php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Maxiviper117\ResultFlow\Result;

final class InventoryService
{
    public function update(array $input): Result
    {
        return Result::ok($input)
            ->then(fn ($data) => $this->validate($data))
            ->then(fn ($data) => $this->runTransaction($data));
    }

    private function validate(array $data): Result
    {
        if (!isset($data['sku'], $data['qty'])) {
            return Result::fail('Missing sku or qty');
        }

        return Result::ok($data);
    }

    private function runTransaction(array $data): Result
    {
        return Result::of(function () use ($data) {
            return DB::transaction(function () use ($data) {
                return Result::ok($data)
                    ->thenUnsafe(fn ($payload) => $this->decrementStock($payload))
                    ->thenUnsafe(fn ($payload) => $this->writeAuditLog($payload))
                    ->throwIfFail();
            });
        });
    }

    private function decrementStock(array $data): Result
    {
        // throw if stock is insufficient (will rollback)
        // update stock and return Result::ok($data)
        return Result::ok($data);
    }

    private function writeAuditLog(array $data): Result
    {
        // may throw on DB error
        return Result::ok($data);
    }
}
```

## Controller

```php
namespace App\Http\Controllers;

use App\Services\InventoryService;
use Illuminate\Http\Request;

final class InventoryController
{
    public function update(Request $request, InventoryService $inventory)
    {
        return $inventory->update($request->all())->toResponse();
    }
}
```

Notes:
- `thenUnsafe()` lets exceptions bubble inside the transaction.
- `throwIfFail()` escalates Result failures into exceptions so Laravel will rollback.
- Wrapping the transaction in `Result::of()` converts thrown exceptions back into a failure.

## Result functions used

- `ok()`, `then()`, `thenUnsafe()`, `throwIfFail()`, `of()`, `fail()`
