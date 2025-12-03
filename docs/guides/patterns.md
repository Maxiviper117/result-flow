---
title: Usage Patterns
---

# Usage Patterns

## Compose Pipelines

Chain success-only steps with `then()` and short-circuit on failure:

```php
Result::ok($data, ['request_id' => $rid])
    ->then(new ValidateCommand)
    ->ensure(fn($cmd) => $cmd->isAuthorized(), 'Unauthorized')
    ->then(new PersistCommand)
    ->then(fn($aggregate, $meta) => Result::ok($aggregate, [...$meta, 'stored' => true]));
```

## Validate Inline

Use `ensure()` for guard clauses instead of ad-hoc `if` checks:

```php
Result::ok($user)
    ->ensure(fn($u) => $u->isActive(), 'Inactive user')
    ->ensure(fn($u) => $u->hasRole('admin'), fn($u) => "User {$u->id} lacks admin role");
```

## Recovery Paths

`otherwise()` runs only on failure and can either recover or keep failing:

```php
Result::fail('Primary failed')
    ->otherwise(fn($e) => Result::ok($cache->get('fallback'))) // recovery
    ->otherwise(fn($e) => Result::fail($e));                    // continued failure
```

Recover with a plain value:

```php
->otherwise(fn($e) => $defaultValue); // wrapped as Result::ok($defaultValue)
```

## Exception Handling

Convert thrown exceptions to failures with `of()` or rely on `then()`'s try/catch:

```php
Result::ok($payload)
    ->then(fn($p) => risky($p)) // exceptions become fail(Throwable)
    ->catchException([
        \InvalidArgumentException::class => fn($e) => Result::fail('Bad input'),
        \RuntimeException::class => fn($e) => Result::fail('Service unavailable'),
    ], fallback: fn($error) => Result::fail($error));
```

Need bubbling for DB transactions? Use `thenUnsafe()` and `throwIfFail()`:

```php
DB::transaction(fn() => Result::ok($dto)
    ->thenUnsafe(new ValidateOrderAction)->throwIfFail()
    ->thenUnsafe(new ChargePaymentAction)->throwIfFail());
```

## Combine Multiple Results

- Use `combine()` when the first failure should stop processing (fail-fast).
- Use `combineAll()` when you want all errors collected (validation).

```php
$validation = Result::combineAll([
    $this->validateEmail($input),
    $this->validatePassword($input),
]);
```

## Pattern Matching at Boundaries

Force both branches to be handled with `match()`:

```php
return $result->match(
    onSuccess: fn($data, $meta) => response()->json($data),
    onFailure: fn($error, $meta) => response()->json(['error' => $error], 400),
);
```

When the error is a Throwable, use `matchException()` to branch by class:

```php
$result->matchException(
    [
        HttpException::class => fn($e) => retryLater(),
        ValidationException::class => fn($e) => showErrors($e->errors()),
    ],
    onSuccess: fn($v) => show($v),
    onUnhandled: fn($e) => fail($e),
);
```

## Metadata for Audit and Telemetry

```php
Result::ok($payload, ['correlation_id' => $cid])
    ->mergeMeta(['started_at' => microtime(true)])
    ->then(fn($value, $meta) => Result::ok(transform($value), [...$meta, 'transformed' => true]))
    ->tapMeta(fn($meta) => Metrics::timing('pipeline', $meta['started_at'] ?? null));
```
