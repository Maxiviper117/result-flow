# Transaction rollback

## What this page is for

Ensure a failed Result causes a database transaction to roll back.

## When to use this

Use this pattern when writes have occurred and an expected failure must undo them. Laravel commits when its transaction closure returns normally; returning `Result::fail(...)` alone is a normal return.

```php
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\UnwrapFailure;
use Illuminate\Support\Facades\DB;

try {
    $order = DB::transaction(function () use ($input, $orders) {
        $orders->insertDraft($input);
        return $orders->validateAndSave($input)->unwrap();
    });
    $result = Result::ok($order);
} catch (UnwrapFailure $failure) {
    $result = Result::fail($failure->failure);
}
```

`unwrap()` rethrows a Throwable failure unchanged, so handle any expected Throwable separately outside the transaction. In nested transactions, do not catch the rollback signal before the framework sees it. Consult the [Laravel transaction documentation](https://laravel.com/docs/12.x/database#database-transactions).

## Related pages

[Boundary reference](/reference/boundaries) and [API boundary recipe](/recipes/api-boundary-mapping).
