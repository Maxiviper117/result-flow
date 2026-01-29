---
title: Getting Started
---

# Getting Started

Result Flow is a lightweight Result/Either type for PHP. It models a value that is either ok (success) with a payload or fail (failure) with an error, plus metadata that travels with every step.

## Requirements

- PHP 8.2+
- Composer

## Installation

```bash
composer require maxiviper117/result-flow
```

Import the `Result` class:

```php
use Maxiviper117\ResultFlow\Result;
```

## Your first Result

Create a success result with `ok()` or a failure result with `fail()`.

```php
$ok = Result::ok(['id' => 1]);
$fail = Result::fail('User not found');
```

## Key concepts at a glance

- Explicit branches: success uses `then()`/`map()`/`ensure()`, failure uses `otherwise()`/`catchException()`.
- Metadata travels with every step and can be merged or replaced.
- Exceptions are captured by default (`then()`/`of()`), but you can opt into `thenUnsafe()` to let them bubble.
- Unwrapping (`unwrap*`) is the escape hatch when you need a raw value or exception.
- Debug output (`toDebugArray()`) sanitizes sensitive metadata for logging.

If you are unsure which method to reach for, start with `then()` for success steps, `otherwise()` for failure steps, and finish with `match()` or `unwrap*` at the boundary where you need a real value.

## Basic pipeline (success + failure)

`then()` runs on success; `otherwise()` runs on failure. Chains short-circuit automatically.

```php
$result = Result::ok($payload)
    ->ensure(fn ($v) => $v['email'] ?? false, 'Email required')
    ->map(fn ($v) => normalize($v))
    ->then(new PersistUser)
    ->otherwise(fn ($e, $meta) => Result::fail("Could not save: {$e}", $meta));
```

## Handling the outcome

Use `match()` to handle both branches explicitly.

```php
$response = $result->match(
    onSuccess: fn ($user, $meta) => ['user' => $user, 'meta' => $meta],
    onFailure: fn ($error, $meta) => ['error' => $error, 'meta' => $meta],
);
```

## Getting values out (unwrap)

`unwrap()` is the shortest path to the value. It returns the success value or throws on failure.

```php
$id = Result::ok(42)->unwrap();

try {
    Result::fail('missing')->unwrap();
} catch (RuntimeException $e) {
    // handle or rethrow
}
```

Prefer safe defaults when you cannot throw:

```php
$user = $result->unwrapOr(new GuestUser());
$cached = $result->unwrapOrElse(fn ($error, $meta) => loadFromCache($meta));
```

If you want custom exceptions:

```php
$dto = $result->getOrThrow(fn ($error, $meta) => new DomainException($error));
```

## Working with metadata

Metadata is an associative array that flows through every operation.

```php
$result = Result::ok($user, ['request_id' => $rid])
    ->mergeMeta(['started_at' => microtime(true)])
    ->then(fn ($u, $meta) => Result::ok($u, [...$meta, 'step' => 'validated']))
    ->tapMeta(fn ($meta) => Log::debug('meta', $meta));
```

Use metadata for correlation IDs, audit trails, or to preserve failed input via `failWithValue($error, $failedValue, $meta = [])`.

For safe logging, use `toDebugArray()` which redacts sensitive keys and truncates long strings:

```php
Log::info('result', $result->toDebugArray());
```

## Safe vs unsafe chaining

- `then()` wraps each step in try/catch and returns `Result::fail($exception)` with `failed_step` in meta.
- `thenUnsafe()` lets exceptions bubble. Pair with `throwIfFail()` to escalate Result failures into exceptions.

```php
DB::transaction(function () use ($dto, $meta) {
    return Result::ok($dto, $meta)
        ->thenUnsafe(new ValidateOrderAction)->throwIfFail()
        ->thenUnsafe(new ChargePaymentAction)->throwIfFail();
});
```

## Retrying operations (quick look)

Use `Result::retry()` for simple retry logic or `Result::retrier()` for advanced controls.

```php
$result = Result::retry(3, fn () => $api->call(), delay: 100, exponential: true);
```

## Returning HTTP responses

At boundaries (controllers, handlers), you can return a response directly:

```php
return $result->toResponse(); // JsonResponse in Laravel, array fallback otherwise
```

## Laravel integration (optional)

The service provider is auto-discovered in Laravel projects. If you want to customize debug sanitization for `toDebugArray()`, publish the config:

```bash
php artisan vendor:publish --tag=result-flow-config
```

Then edit `config/result-flow.php`:

```php
return [
    'debug' => [
        'enabled' => true,
        'redaction' => '***REDACTED***',
        'sensitive_keys' => ['password', 'token', 'ssn', 'card'], // supports '*' and '?' globs
        'max_string_length' => 200,
        'truncate_strings' => true,
    ],
];
```

## Next steps

- Read the [Result deep dive](/result/) to learn about constructors, chaining, and matching.
- Explore [Retrying operations](/result/retrying) for advanced retry rules.
- Review [Debugging & Metadata](/debugging) and [Sanitization & Safety](/sanitization) for safe logging.
- Use the [API reference](/api) for the full method catalog with examples.
