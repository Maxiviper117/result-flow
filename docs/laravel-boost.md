# Laravel Boost

The package ships downstream application guidance in `resources/boost/guidelines/core.blade.php` and a central skill in `resources/boost/skills/result-flow/` with a local `references/api.md`. The guidance recommends explicit Result chains, exception boundaries, and response field mapping. Consult the [Laravel Boost documentation](https://laravel.com/docs/12.x/boost) for installation and discovery in your application.

Returning a Fail from a Laravel transaction does not cause rollback. See [transaction rollback](/guides/transaction-rollback) and the [reference](/reference/).

## Return a response from a controller

Keep the action responsible for its domain outcome and let the controller choose HTTP status codes and public fields. In this example your application's `CreateOrder` action returns a success containing an order ID or an expected validation failure. The client receives a fixed public message even if the underlying failure carries internal details.

```php
use App\Actions\CreateOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController
{
    public function __invoke(Request $request, CreateOrder $create): JsonResponse
    {
        return $create($request->string('name')->toString())->match(
            onSuccess: fn (int $id): JsonResponse => response()->json(['id' => $id], 201),
            onFailure: fn (mixed $error): JsonResponse => response()->json(['error' => 'Invalid order'], 422),
        );
    }
}
```

If an action can also report a conflict or a missing record, select the corresponding status by inspecting its error in the failure callback. For database actions that must undo writes, follow the [transaction guide](/guides/transaction-rollback).

## Configure diagnostic output

The package service provider merges the default settings when Laravel boots. To customize redaction and traversal limits, publish the config in your Laravel application:

```sh
php artisan vendor:publish --tag=result-flow-config
```

Edit `config/result-flow.php` and include application-specific sensitive keys alongside the defaults. The [debug reference](/reference/metadata-debugging) describes which fields are sanitized and how array and object limits work.
