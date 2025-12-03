---
title: Transforming and chaining
---

# Transforming and chaining

`Result` supports fluent chains that stay on the success path or branch to the failure path. This page shows how to transform values, validate inline, and orchestrate sequential steps with clear examples.

## Mapping and validation

### Transform a success value with `map()`

```php
$user = Result::ok($payload)
    ->map(fn($data) => hydrateUser($data))
    ->map(fn(User $user) => $user->withLocale('en'));
```

### Transform the error with `mapError()`

```php
$normalized = Result::fail(new ValidationException('Bad email'))
    ->mapError(fn(Throwable $e) => $e->getMessage());

// $normalized->error() === 'Bad email'
```

### Validate inline with `ensure()`

```php
$ready = Result::ok($order)
    ->ensure(fn(Order $o) => $o->isPaid(), 'Unpaid order')
    ->ensure(fn($o) => $o->items()->count() > 0, 'No line items');
```

If the predicate fails, `ensure()` returns `Result::fail($error, $meta)` while keeping the metadata untouched.

## Success-path chaining

### `then()` sequences steps safely

`then()` wraps each step in `try/catch`. Returning a `Result` propagates its state; returning a raw value is wrapped as success.

```php
$pipeline = Result::ok($payload, ['request_id' => $rid])
    ->then(fn($data, $meta) => validate($data, $meta))
    ->then(fn($validated, $meta) => transform($validated, ['step' => 'transformed'] + $meta))
    ->then(fn($dto, $meta) => persist($dto, $meta));
```

### `thenUnsafe()` lets exceptions bubble

Use it when you want to fail loudly but still keep the fluent shape. Pair with `throwIfFail()` at the end if you want to convert failures back into exceptions.

```php
$result = Result::ok($payload)
    ->thenUnsafe(fn($data) => riskyWrite($data))
    ->throwIfFail(); // throws the Throwable from riskyWrite() if present
```

### Run multiple steps with arrays or invokable objects

`then()` accepts arrays of steps and invokable objects. Each step receives the current value and metadata.

```php
$result = Result::ok($payload)
    ->then([
        new SanitizeInput(),
        fn($clean) => Result::ok($clean, ['step' => 'sanitized']),
        new PersistUser(), // has __invoke(User $user, array $meta)
    ]);
```

## Failure-path chaining

### `otherwise()` for recovery or continued failure

Return a success to recover, or another `Result::fail()` to keep the failure state.

```php
$user = Result::fail('Unavailable')
    ->otherwise(fn($error) => cache()->get('user_backup') ?? Result::fail($error));
```

### `catchException()` targets specific Throwable types

```php
$safe = Result::of(fn() => $service->call())
    ->catchException([
        \InvalidArgumentException::class => fn($e) => Result::fail('Bad input'),
        \RuntimeException::class => fn($e) => Result::fail('Service down'),
    ], fallback: fn($error) => Result::fail($error));
```

### `recover()` always produces success

```php
$settings = Result::fail('missing-config')
    ->recover(fn($error) => loadDefaults());
```

`recover()` is useful when a downstream consumer cannot handle failures and you have a safe fallback value.
