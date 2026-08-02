---
title: Generator Composition
---

# Generator Composition

`Result::flow()` lets a workflow read several successful values without nested callbacks.

The API uses a synchronous PHP generator. Each `yield` must produce a `Result`.

## Basic example

```php
use Generator;
use Maxiviper117\ResultFlow\Result;

$result = Result::flow(function (): Generator {
    $user = yield findUser($userId);
    $account = yield findAccount($user->accountId);
    $permissions = yield loadPermissions($user->id);

    return createInvoice($user, $account, $permissions);
});
```

Each successful result sends its value back into the generator.

## Short-circuit behavior

The first failed result stops the workflow.

Later statements do not run.

```php
$result = Result::flow(function (): Generator {
    $user = yield findUser($userId);
    $invoice = yield createInvoice($user);

    return $invoice;
});
```

If `findUser()` fails, the flow returns that failure.

## Returning values and Results

Return a plain value to create `Result::ok($value)`.

```php
return $invoice;
```

Return a `Result` when the final operation already uses ResultFlow.

```php
return createInvoice($user);
```

An empty generator returns `Result::ok(null)` when it has no explicit return.

## Metadata behavior

The flow merges metadata from each yielded result.

Later successful results overwrite earlier keys.

Failure metadata overwrites accumulated success metadata.

Final returned Result metadata overwrites all earlier metadata.

Use namespaced keys when different steps need the same key name.

```php
Result::ok($user, ['user.lookup.duration_ms' => 12]);
```

## Exception behavior

`flow()` does not catch unexpected exceptions.

The exception escapes the flow.

Use `Result::of()` or `Result::defer()` before a yield when an operation should convert an exception to failure.

Use `Result::tryFlow()` when the complete workflow should convert unexpected exceptions to failure values.

```php
$result = Result::tryFlow(function (): Generator {
    $user = yield findUser($userId);

    return $user;
});
```

## Cleanup limitations

The runner destroys a suspended generator after a failed yield or an exception.

PHP then runs the generator `finally` blocks.

Cleanup exceptions escape and can replace the original exception.

Use `Result::bracket()` for critical resource acquire, use, and release logic.

## When to use `flow()`

Use `flow()` for:

- orchestration services
- multi-step integrations
- use cases that need several earlier values
- transaction-like application logic

Use fluent methods for short chains, simple transformations, validation, and error mapping.

## When to use fluent chaining

This fluent chain suits a short workflow:

```php
$result = findUser($userId)
    ->then(fn (User $user) => findAccount($user->accountId))
    ->then(fn (Account $account) => createInvoice($account));
```

Use `flow()` when nested callbacks hide the workflow order or when several earlier values remain in use.

## PHPStan limitations

The public method uses PHPDoc generics.

PHPStan may infer a value assigned from `yield` as `mixed`.

Add a local annotation when the inferred type does not meet the application need.

The runtime still validates every yielded value.

Use `Result::bind(...)` with `yield from` when the value type needs stronger inference.

```php
$user = yield from Result::bind(findUser($userId));
```

## Unsupported yield forms

The first version supports synchronous generators only.

Do not yield plain values, callables, promises, or futures.

Do not use asynchronous runtimes, parallel execution, or cancellation with `flow()`.

Use `yield from Result::bind(...)` for nested Result workflows.

## Migration examples

Replace nested fluent callbacks when several values must stay in scope.

```php
// Fluent
$result = findUser($userId)
    ->then(fn (User $user) => findAccount($user->accountId)
        ->then(fn (Account $account) => createInvoice($user, $account)));

// Generator
$result = Result::flow(function (): Generator {
    $user = yield findUser($userId);
    $account = yield findAccount($user->accountId);

    return createInvoice($user, $account);
});
```

Keep the fluent form when it remains easier to scan.

## Related pages

- [Constructing results](/concepts/constructing)
- [Construction reference](/reference/construction)
- [Resource safety](/concepts/resource-safety)
