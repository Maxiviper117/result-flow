---
title: Transforming and chaining
---

# Transforming and chaining

`Result` supports fluent chains that stay on the success path or branch to the failure path. This page focuses on how data and metadata move through chains and how short-circuiting works.

## How chaining works

- Success steps (`then`, `map`, `ensure`) only run when the result is ok.
- Failure steps (`otherwise`, `catchException`) only run when the result is failed.
- Returning a `Result` from a step replaces the current result and propagates its metadata.
- Returning a raw value wraps it as `Result::ok($value, $meta)`.

## Mapping and validation

### Transform a success value with `map()`

```php
$user = Result::ok($payload)
    ->map(fn ($data, $meta) => hydrateUser($data))
    ->map(fn (User $user) => $user->withLocale('en'));
```

`map()` is for value transformation only. If you need to return a `Result`, use `then()` instead.

### Transform the error with `mapError()`

```php
$normalized = Result::fail(new ValidationException('Bad email'))
    ->mapError(fn (Throwable $e, $meta) => $e->getMessage());

$normalized->error(); // 'Bad email'
```

`mapError()` is useful for normalizing error types or turning Throwables into user-facing messages.

### Validate inline with `ensure()`

```php
$ready = Result::ok($order)
    ->ensure(fn (Order $o, $meta) => $o->isPaid(), 'Unpaid order')
    ->ensure(fn ($o) => $o->items()->count() > 0, 'No line items');
```

`ensure()` behavior:
- If the current result is a failure, it short-circuits and the predicate is not called.
- If the predicate returns false, it becomes a failure with the provided error.
- If the error argument is a callable (and not a string), it is called with `(value, meta)`.

## Success-path chaining

### `then()` sequences steps safely

`then()` wraps each step in try/catch. Returning a `Result` propagates its state; returning a raw value is wrapped as success.

```php
$pipeline = Result::ok($payload, ['request_id' => $rid])
    ->then(fn ($data, $meta) => validate($data, $meta))
    ->then(fn ($validated, $meta) => transform($validated, ['step' => 'transformed'] + $meta))
    ->then(fn ($dto, $meta) => persist($dto, $meta));
```

When a step throws, `then()` returns a failure and adds `meta['failed_step']` with the step name (class or `Class::method`).

If a step returns a raw value (not a Result), it is wrapped as `Result::ok($value, $meta)`.

### Run multiple steps with arrays or invokable objects

`then()` accepts arrays of steps and invokable objects. Each step receives `(value, meta)`.

```php
$result = Result::ok($payload)
    ->then([
        new SanitizeInput(),
        fn ($clean) => Result::ok($clean, ['step' => 'sanitized']),
        new PersistUser(), // has __invoke(User $user, array $meta)
    ]);
```

Callable arrays like `[$service, 'handle']` are treated as a single step (not split into two).

### `flatMap()` is an alias for `then()`

```php
$result = Result::ok(3)
    ->flatMap(fn ($v) => Result::ok($v + 1));
```

### `thenUnsafe()` lets exceptions bubble

Use it when you want exceptions to escape (for example, DB transactions). It still short-circuits on failures.

```php
$result = Result::ok($payload)
    ->thenUnsafe(fn ($data) => riskyWrite($data))
    ->throwIfFail();
```

If a step returns a non-Result value, it is wrapped as `Result::ok($value, $meta)`.

## Failure-path chaining

### `otherwise()` for recovery or continued failure

Return a success to recover, or another `Result::fail()` to keep the failure state.

```php
$user = Result::fail('Unavailable')
    ->otherwise(fn ($error, $meta) => cache()->get('user_backup') ?? Result::fail($error));
```

`otherwise()` accepts the same step shapes as `then()` (callables, objects, arrays). If you return a raw value, it becomes a success.

### `catchException()` targets specific Throwable types

```php
$safe = Result::of(fn () => $service->call())
    ->catchException([
        InvalidArgumentException::class => fn ($e, $meta) => Result::fail('Bad input'),
        RuntimeException::class => fn ($e, $meta) => Result::fail('Service down'),
    ], fallback: fn ($error, $meta) => Result::fail($error));
```

Handlers may return a `Result` or a raw value (raw values are wrapped as `Result::ok`).
If the error is not a Throwable (or no handler matches), the fallback runs when provided.

### `recover()` always produces success

```php
$settings = Result::fail('missing-config')
    ->recover(fn ($error, $meta) => loadDefaults());
```

`recover()` is useful when a downstream consumer cannot handle failures and you have a safe fallback value.
