---
title: Chaining and Transforming
---

# Chaining and Transforming

## What this page is for

Use this page for success-path flow: transform values, validate inline, and chain follow-up steps.

## `map()` vs `then()`

- `map(fn)` transforms a success value into another plain value.
- `then(fn)` runs a step that can return either plain value or another `Result`.

```php
$result = Result::ok(2)
    ->map(fn (int $v) => $v * 10)
    ->then(fn (int $v) => Result::ok("value={$v}"));
```

## `mapError()`

Use when failure value exists but you want to normalize shape.

```php
$normalized = Result::fail(['code' => 500])
    ->mapError(fn (array $e) => "code={$e['code']}");
```

## `ensure()`

Inline validation for successful values.

```php
$validated = Result::ok(['total' => 99])
    ->ensure(
        fn (array $order) => $order['total'] > 0,
        fn (array $order) => "Invalid total: {$order['total']}"
    );
```

Behavior:
- Runs only on success branch.
- If predicate returns false, converts to failure using error value/factory.

## `then()` and `flatMap()`

`flatMap()` is an alias for `then()`.

```php
$next = Result::ok($dto)
    ->then(new ValidateAction)
    ->flatMap(fn ($validated, array $meta) => persist($validated, $meta));
```

Behavior:
- On success, invokes step with `(value, meta)`.
- If step returns plain value, it is wrapped as `ok(value, meta)`.
- Exceptions are caught and converted to failure.

## `thenUnsafe()`

Use when exceptions must bubble (for transaction rollback behavior).

```php
$dbResult = Result::ok($dto)
    ->thenUnsafe(new ValidateAction)
    ->thenUnsafe(new PersistAction)
    ->throwIfFail();
```

Behavior:
- Does not catch exceptions.
- Accepts callable/object step and supports `Result` or plain value returns.

## Tap methods on success/failure paths

- `tap()` runs on both branches.
- `onSuccess()`/`inspect()` run only on success.
- `onFailure()`/`inspectError()` run only on failure.

These methods never change branch or payload; they are for side effects.

## Related pages

- [Error Handling](/result/error-handling)
- [Matching and Unwrapping](/result/matching-unwrapping)
- [API Reference](/api)
