---
title: Getting Started
---

# Getting Started

Result Flow is a lightweight Result/Either type for PHP. It models a value that is either **ok** (success) with a payload or **fail** (failure) with an error, plus metadata that travels with every step.

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

## Your First Result

Create a success result with `ok()` or a failure result with `fail()`.

```php
$ok = Result::ok(['id' => 1]);
$fail = Result::fail('User not found');
```

## Basic Pipeline (Success + Failure)

`then()` runs on success; `otherwise()` runs on failure. Chains short-circuit automatically.

```php
$result = Result::ok($payload)
    ->ensure(fn($v) => $v['email'] ?? false, 'Email required')
    ->map(fn($v) => normalize($v))
    ->then(new PersistUser)
    ->otherwise(fn($e, $meta) => Result::fail("Could not save: {$e}", $meta));
```

## Handling the Outcome

Use `match()` to handle both branches explicitly.

```php
$response = $result->match(
    onSuccess: fn($user, $meta) => ['user' => $user, 'meta' => $meta],
    onFailure: fn($error, $meta) => ['error' => $error, 'meta' => $meta],
);
```

### Shortcuts

- `unwrap()` returns the value or throws (rethrows the original `Throwable` when used as the error).
- `unwrapOr($default)` returns a fallback value.
- `unwrapOrElse(fn($error, $meta) => ...)` computes a fallback lazily.
- `getOrThrow(fn($error, $meta) => new DomainException(...))` maps a failure into a custom exception.

## Working with Metadata

Metadata is an associative array that flows through every operation.

```php
$result = Result::ok($user, ['request_id' => $rid])
    ->mergeMeta(['started_at' => microtime(true)])
    ->then(fn($u, $meta) => Result::ok($u, [...$meta, 'step' => 'validated']))
    ->tapMeta(fn($meta) => Log::debug('meta', $meta));
```

Use metadata for correlation IDs, audit trails, or to preserve failed input via `failWithValue($error, $failedValue, $meta = [])`.

## Safe vs Unsafe Chaining

- `then()` wraps each step in `try/catch` and returns `Result::fail($exception)` with `failed_step` in meta.
- `thenUnsafe()` lets exceptions bubble. Pair with `throwIfFail()` to escalate Result failures into exceptions.

```php
DB::transaction(function () use ($dto, $meta) {
    return Result::ok($dto, $meta)
        ->thenUnsafe(new ValidateOrderAction)->throwIfFail()
        ->thenUnsafe(new ChargePaymentAction)->throwIfFail();
});
```

## Retrying Operations (Quick Look)

Use `Result::retry()` for simple retry logic or `Result::retrier()` for advanced controls.

```php
$result = Result::retry(3, fn() => $api->call(), delay: 100, exponential: true);
```

## Laravel Integration (Optional)

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

## Next Steps

- Read the [Result Deep Dive](/result/) to learn about constructors, chaining, and matching.
- Explore [Retrying Operations](/result/retrying) for advanced retry rules.
- Review [Debugging & Meta](/debugging) and [Sanitization & Safety](/sanitization) for safe logging.
