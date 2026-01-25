---
title: Getting Started
---

# Getting Started

Result Flow is a lightweight Result/Either monad for PHP. It models a value that is either **ok** with a success payload or **fail** with an error payload, plus metadata that travels with every step.

## Installation

```bash
composer require maxiviper117/result-flow
```

Import the class where you use it:

```php
use Maxiviper117\ResultFlow\Result;
```

### Optional: Laravel config override

When Laravel's `config()` helper is available, `Result::toDebugArray()` reads `config/result-flow.php` to change sanitization defaults:

```php
return [
    'debug' => [
        'enabled' => true,
        'redaction' => '***REDACTED***',
        'sensitive_keys' => ['password', 'token', 'ssn', 'card'], // supports glob patterns '*' and '?'
        'max_string_length' => 200,
        'truncate_strings' => true,
    ],
];
```

Publish with:

```bash
php artisan vendor:publish --tag=result-flow-config
```

## Core Concept

- `Result::ok($value, $meta = [])` holds a success value.
- `Result::fail($error, $meta = [])` holds an error.
- Every method either returns a new `Result` or the unwrapped value.
- Chains short-circuit on failure: `then()` runs only on success; `otherwise()` runs only on failure.

## Smallest Pipeline

```php
$result = Result::ok($payload)
    ->ensure(fn($v) => $v['email'] ?? false, 'Email required')
    ->map(fn($v) => normalize($v))
    ->then(new PersistUser)
    ->otherwise(fn($e) => Result::fail("Could not save: {$e}"));
```

## Handling the Outcome

```php
$response = $result->match(
    onSuccess: fn($user, $meta) => ['user' => $user, 'meta' => $meta],
    onFailure: fn($error, $meta) => ['error' => $error, 'meta' => $meta],
);
```

### Pattern Matching Shortcuts

- `unwrap()` returns the value or throws (throws the original `Throwable` if it was the error).
- `unwrapOr($default)` returns the value or the default.
- `unwrapOrElse(fn($error) => ...)` computes a fallback lazily.
- `getOrThrow(fn($error, $meta) => new DomainException(...))` maps the error into a custom exception.

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

- `then()` wraps the step in try/catch. Exceptions become `Result::fail($exception)` with `failed_step` in meta.
- `thenUnsafe()` lets exceptions bubble (useful in DB transactions where you want exceptions to trigger rollback). Pair with `throwIfFail()` to escalate failures into thrown exceptions.

```php
DB::transaction(function () use ($dto, $meta) {
    return Result::ok($dto, $meta)
        ->thenUnsafe(new ValidateOrderAction)->throwIfFail()
        ->thenUnsafe(new ChargePaymentAction)->throwIfFail();
});
```

## Combining Results

```php
$combined = Result::combine([
    $validateName($data),
    $validateEmail($data),
]); // fail-fast on first failure

$all = Result::combineAll([
    $validateName($data),
    $validateEmail($data),
]); // collects all errors into array
```
