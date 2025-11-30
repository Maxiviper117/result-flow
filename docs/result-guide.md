# Result Flow

A lightweight, type-safe Result monad for explicit success/failure handling in PHP.

> **Location:** `src/Result.php`  
> **Namespace:** `Maxiviper117\ResultFlow\Result`

## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [API Reference](#api-reference)
  - [Static Constructors](#static-constructors)
  - [Checking State](#checking-state)
  - [Accessing Values](#accessing-values)
  - [Transforming Values](#transforming-values)
  - [Chaining Operations](#chaining-operations)
  - [Side Effects](#side-effects)
  - [Metadata Operations](#metadata-operations)
  - [Pattern Matching & Unwrapping](#pattern-matching--unwrapping)
    - [Exception Pattern Matching & Handling](#exception-pattern-matching--handling)
  - [Combining Results](#combining-results)
- [Usage Patterns](#usage-patterns)
- [Anti-Patterns](#anti-patterns)
- [Internal Architecture](#internal-architecture)
- [Type Safety](#type-safety)
- [Testing](#testing)
- [Quick Reference](#quick-reference)

---

## Overview

The `Result` type provides:

| Feature                 | Description                                                                  |
| ----------------------- | ---------------------------------------------------------------------------- |
| **Explicit branching**  | Clear separation between success and failure paths                           |
| **Fluent chaining**     | Pipeline-style composition with `then()` and `otherwise()`                   |
| **Type inference**      | PHPStan/Psalm templates for `TSuccess` and `TFailure`                        |
| **Context propagation** | Carry metadata through the entire pipeline via `meta`                        |
| **Exception safety**    | Automatic exception-to-failure conversion within chains                      |
| **Exception matching**  | Class-aware exception matching via `catchException()` and `matchException()` |
| **Pattern matching**    | Exhaustive `match()` for handling both branches                              |
| **Validation support**  | `ensure()` for inline predicate checks                                       |
| **Batch operations**    | `combine()` and `combineAll()` for multiple results                          |

Think of it as a lightweight `Either` monad with a practical, action-oriented interface.

- The `predicate` uses PHP truthiness (non-empty / non-false values are treated as pass). If you need strict boolean checks use an explicit comparison that returns a bool. Note: the library treats any truthy value as success, following PHP's loose convention.

---

## Installation

The Result type ships with the package; install via Composer:

```bash
composer require maxiviper117/result-flow
```

Import it in your code:

```php
use Maxiviper117\ResultFlow\Result;
```

### Optional: Laravel config override

The package is framework-agnostic, but if you're using Laravel the service provider is auto-discovered and you can override the default debug sanitizer settings (redaction token, sensitive keys, and max string length) by publishing the config:

```bash
php artisan vendor:publish --tag=result-flow-config
```

Edit `config/result-flow.php` to match your policies:

```php
return [
    'debug' => [
        'redaction' => '***REDACTED***',
        'sensitive_keys' => ['password', 'token', 'ssn', 'card'],
        'max_string_length' => 200,
    ],
];
```

When Laravel's `config()` helper is available, `Result::toDebugArray()` will use these settings automatically. In non-Laravel environments it falls back to the built-in defaults.

---

## Quick Start

### Basic Success/Failure

```php
// Create a success result
$success = Result::ok($user);

// Create a failure result
$failure = Result::fail('User not found');

// Check the state
if ($success->isOk()) {
    echo $success->value()->name;
}

if ($failure->isFail()) {
    echo $failure->error(); // "User not found"
}
```

### Chaining Actions

```php
$result = Result::ok($orderData)
    ->then(new ValidateOrder)       // Runs if previous succeeded
    ->then(new ProcessPayment)      // Runs if previous succeeded
    ->then(new SendConfirmation)    // Runs if previous succeeded
    ->onFailure(fn($e) => Log::error('Order failed', ['error' => $e]));
```

### Pattern Matching

```php
return $result->match(
    onSuccess: fn($user) => response()->json($user),
    onFailure: fn($error) => response()->json(['error' => $error], 400),
);
```

### Wrapping Risky Operations

```php
$result = Result::of(fn() => $api->fetchUser($id));

// Exceptions become Result::fail($exception)
if ($result->isFail() && $result->error() instanceof HttpException) {
    return response()->json(['error' => 'API unavailable'], 503);
}
```

---

## API Reference

### Static Constructors

#### `Result::ok(mixed $value, array $meta = []): Result`

Creates a success result with the given value.

```php
$result = Result::ok($user);
$result = Result::ok($user, ['source' => 'database']);
```

#### `Result::fail(mixed $error, array $meta = []): Result`

Creates a failure result with the given error.

```php
$result = Result::fail('Validation failed');
$result = Result::fail(new ValidationException($errors));
$result = Result::fail(['field' => 'email', 'message' => 'Invalid format']);
```

#### `Result::failWithValue(mixed $error, mixed $failedValue, array $meta = []): Result`

Creates a failure that preserves the input that caused the failure. The failed value is stored in `meta['failed_value']`.

```php
$input = ['email' => 'invalid'];
$result = Result::failWithValue('Validation failed', $input);

// Notes:
// - `failed_value` is placed at the start of the metadata array so it is
//   easy to find and is available to logging/telemetry consumers.
// - If the consumer passes `failed_value` in $meta, it will override the
//   default failed_value passed to the helper.
// Later in error handling:
$result->onFailure(function ($error, $meta) {
    Log::warning($error, ['input' => $meta['failed_value']]);
});
```

#### `Result::of(callable $fn): Result`

Wraps a callable and converts thrown exceptions into failures.

```php
// Instead of try/catch:
$result = Result::of(fn() => $httpClient->get('/api/users'));

// Exceptions become Result::fail($exception)
```

#### `Result::combine(array $results): Result`

Combines multiple results into one. Fails on first failure (short-circuit).

```php
$results = [
    (new ValidateName)($data['name']),
    (new ValidateEmail)($data['email']),
];

$combined = Result::combine($results);
// Success: Result::ok([$nameValue, $emailValue])
// Failure: First failed result
```

#### `Result::combineAll(array $results): Result`

Combines results collecting ALL errors (no short-circuit). Useful for validation where you want all errors at once.

```php
$results = [
    Result::fail('Name required'),
    Result::ok('valid@email.com'),
    Result::fail('Age must be positive'),
];

$combined = Result::combineAll($results);
// Result::fail(['Name required', 'Age must be positive'])

// Note: When `combineAll()` returns a failure, `value()` will be `null` and
// `error()` will be an array of the collected errors.
```

---

### Checking State

#### `isOk(): bool`

Returns `true` if the result is a success.

```php
if ($result->isOk()) {
    // Handle success
}
```

#### `isFail(): bool`

Returns `true` if the result is a failure.

```php
if ($result->isFail()) {
    // Handle failure
}
```

---

### Accessing Values

#### `value(): mixed`

Returns the success value, or `null` if failed.

```php
$user = $result->value(); // User|null
```

#### `error(): mixed`

Returns the error payload, or `null` if succeeded.

```php
$error = $result->error(); // mixed|null
```

#### `meta(): array`

Returns the metadata array.

```php
$meta = $result->meta(); // ['request_id' => 'abc123', ...]
```

#### `toArray(): array`

Converts the result to an array for debugging or serialization.

```php
$data = $result->toArray();
// ['ok' => true, 'value' => ..., 'error' => null, 'meta' => [...]]
```

#### `toDebugArray(?callable $sanitizer = null): array`

Converts the result to a debug-safe array (hides sensitive data, shows types). You can pass a custom sanitizer to control redaction/truncation; by default it redacts values for keys containing `password`, `secret`, `token`, `api_key`, `ssn`, `card`, etc., and truncates long strings. In Laravel, these defaults can be overridden via `config/result-flow.php` (see installation section).

```php
$data = $result->toDebugArray();
// [
//     'ok' => false,
//     'value_type' => null,
//     'error_type' => 'RuntimeException',
//     'error_message' => 'Connection failed',
//     'meta' => [...]
// ]
```

Note: `error_message` will be populated for `Throwable` errors and for string errors; for other error types (arrays, objects without string representation) it will be null to avoid leaking sensitive information.
The default sanitizer also recurses through `meta` and nested arrays, replacing sensitive-looking keys with `***REDACTED***` and shortening very long strings to avoid leaking full tokens or dumps. Provide your own `$sanitizer` if you need different logic.

---

### Transforming Values

#### `map(callable $fn): Result`

Transforms the success value. Failures pass through unchanged.

```php
$result = Result::ok(100)
    ->map(fn($cents) => $cents / 100); // Result::ok(1.0)

$result = Result::fail('error')
    ->map(fn($v) => $v * 2); // Still Result::fail('error')
```

**Signature:** `fn(TSuccess $value, array $meta): U`

#### `mapError(callable $fn): Result`

Transforms the error value. Successes pass through unchanged.

```php
$result = Result::fail(new DbException('Connection failed'))
    ->mapError(fn($e) => 'Database unavailable'); // User-friendly message
```

**Signature:** `fn(TFailure $error, array $meta): E`

#### `ensure(callable $predicate, mixed $error): Result`

Validates the success value with a predicate. Fails if predicate returns `false`.

```php
$result = Result::ok($user)
    ->ensure(fn($u) => $u->isActive(), 'User is not active')
    ->ensure(fn($u) => $u->hasRole('admin'), fn($u) => "User {$u->id} lacks admin role")
    ->then(new GrantAccess);
```

**Signature:**
- `$predicate`: `fn(TSuccess $value, array $meta): bool`
- `$error`: Static error value or `fn(TSuccess $value, array $meta): TFailure`

> **Note:** The predicate uses PHP's truthiness — any truthy value (including `1`, `"yes"`, non-empty arrays) passes the check. For explicit boolean logic, ensure your predicate returns a strict `true` or `false`.

---

### Chaining Operations

#### `then(callable|object|array $next): Result`

Chains another operation on success. Short-circuits on failure.

```php
$result = Result::ok($data)
    ->then(new ValidateAction)
    ->then(new SaveAction)
    ->then(fn($saved, $meta) => Result::ok($saved->id));
```

**Accepts:**
- Closures: `fn($value, $meta) => ...`
- Objects with `__invoke($value, $meta)`
- Objects with `handle($value, $meta)`
- Objects with `execute($value, $meta)`
- Arrays of the above (executed sequentially)

**Returns:** The result from the step, or wraps raw values as `Result::ok($value)`.

#### `flatMap(callable $fn): Result`

Alias for `then()` - standard monadic flatMap/bind for developers from FP backgrounds.

```php
$result = Result::ok($userId)
    ->flatMap(fn($id) => $userRepo->find($id))
    ->flatMap(fn($user) => $user->loadRelations());
```

#### `thenUnsafe(callable|object $next): Result`

Chain another operation on success **WITHOUT exception handling**. Unlike `then()`, exceptions will bubble up freely. This is essential for DB transactions where you need the entire transaction to rollback on any failure.

```php
DB::transaction(function () use ($dto, $meta) {
    return Result::ok($dto, $meta)
        ->thenUnsafe(new ValidateOrderAction)   // throws bubble → rollback
        ->thenUnsafe(new PersistOrderAction)    // throws bubble → rollback
        ->thenUnsafe(new ChargePaymentAction);  // throws bubble → rollback
});
```

**Key differences from `then()`:**

| Aspect             | `then()`                                 | `thenUnsafe()`                          |
| ------------------ | ---------------------------------------- | --------------------------------------- |
| Exception handling | Catches and converts to `Result::fail()` | Exceptions bubble up                    |
| Use case           | Safe pipelines, non-transactional code   | DB transactions requiring full rollback |
| Accepts arrays     | Yes                                      | No (single step only)                   |

**Accepts:**
- Closures: `fn($value, $meta) => ...`
- Objects with `__invoke($value, $meta)`
- Objects with `handle($value, $meta)`
- Objects with `execute($value, $meta)`

#### `otherwise(callable|object|array $next): Result`

Chains another operation on failure. Skipped on success.

```php
$result = Result::fail('Primary failed')
    ->otherwise(fn($e) => Result::ok($cachedValue))  // Recover from cache
    ->then(fn($v) => process($v));                   // Continue with recovered value
```

**Recovery vs. Continued Failure:**

```php
// Recovery - returns success, chain continues as success
->otherwise(fn($e) => Result::ok($fallback))

// Continued failure - returns fail, next otherwise() can handle it
->otherwise(fn($e) => Result::fail('Still failing'))

// Raw value = recovery
->otherwise(fn($e) => $fallbackValue)  // Wrapped as Result::ok()
```

#### `recover(callable $fn): Result`

Unconditionally recovers from failure by producing a success.

```php
$result = Result::fail('Not found')
    ->recover(fn($e) => $defaultUser); // Always Result::ok($defaultUser)
```

---

### Side Effects

Side effect methods perform actions without changing the result. They always return `$this`.

#### `tap(callable $fn): Result`

Observes both branches.

```php
$result->tap(fn($value, $error, $meta) =>
    Log::info('Result', compact('value', 'error', 'meta'))
);
```

#### `onSuccess(callable $fn): Result`

Observes only the success branch.

```php
$result->onSuccess(fn($user, $meta) =>
    Log::info('User created', ['id' => $user->id])
);
```

#### `inspect(callable $fn): Result`

Alias for `onSuccess()` - Rust convention.

```php
$result->inspect(fn($user) => Log::info('User loaded', ['id' => $user->id]));
```

#### `onFailure(callable $fn): Result`

Observes only the failure branch.

```php
$result->onFailure(fn($error, $meta) =>
    Log::error('Operation failed', ['error' => $error, ...$meta])
);
```

#### `inspectError(callable $fn): Result`

Alias for `onFailure()` - Rust convention.

```php
$result->inspectError(fn($error) => Log::warning('Failed', ['error' => $error]));
```

---

### Metadata Operations

Metadata flows through the entire chain and can be used for correlation IDs, timing, audit trails, etc.

> **Note:** As of the latest version, metadata is properly propagated through chain steps. When a step returns a Result with updated meta, subsequent steps receive that updated meta.

#### `tapMeta(callable $fn): Result`

Observes metadata without modification.

```php
$result->tapMeta(fn($meta) => Log::debug('Meta', $meta));
```

Note: `tapMeta()` is intended for observation only and does not change the Result's stored metadata. To mutate or replace metadata use `mapMeta()` or `mergeMeta()` instead. The callable receives a copy of the metadata and any in-place modifications inside the callback will not persist to the Result.

#### `mapMeta(callable $fn): Result`

Replaces metadata entirely.

```php
$result->mapMeta(fn($meta) => [...$meta, 'timestamp' => now()]);
```

#### `mergeMeta(array $meta): Result`

Merges additional metadata (shallow merge).

```php
$result->mergeMeta(['correlation_id' => $uuid]);
```

**When to use each:**

| Method      | Use Case                                           |
| ----------- | -------------------------------------------------- |
| `tapMeta`   | Logging, debugging, metrics                        |
| `mapMeta`   | Complete metadata transformation                   |
| `mergeMeta` | Adding correlation IDs, flags, incremental context |

---

### Pattern Matching & Unwrapping

#### `match(callable $onSuccess, callable $onFailure): mixed`

Pattern match on success or failure - forces handling both cases. This is the recommended way to handle results at boundaries.

```php
return $result->match(
    onSuccess: fn($user, $meta) => response()->json($user),
    onFailure: fn($error, $meta) => response()->json(['error' => $error], 400),
);
```

#### `unwrap(): mixed`

Returns the success value or throws.

```php
try {
    $user = $result->unwrap();
} catch (Throwable $e) {
    // Handle error
}
```

**Throwing behavior:**
- If error is `Throwable`, throws it directly
- Otherwise throws `RuntimeException` with error as message

#### `unwrapOr(mixed $default): mixed`

Returns the success value or a default.

```php
$user = $result->unwrapOr(null);
$count = $result->unwrapOr(0);
```

#### `unwrapOrElse(callable $fn): mixed`

Returns the success value or computes a default lazily from the error.

```php
$user = $result->unwrapOrElse(fn($error, $meta) => User::guest());

// Useful when default computation is expensive
$config = $result->unwrapOrElse(fn($e) => $this->loadFallbackConfig());
```

#### `getOrThrow(callable $exceptionFactory): mixed`

Returns the success value or throws a custom exception.

```php
$user = $result->getOrThrow(
    fn($error, $meta) => new UserNotFoundException("User not found: {$error}")
);

// With context from meta
$order = $result->getOrThrow(
    fn($e, $m) => new OrderException($e, $m['order_id'] ?? null)
);
```

#### `throwIfFail(): Result`

Throws if fail; returns `$this` if ok. This method is **chainable** — it returns `$this` on success so you can continue the chain.

Useful to escalate `Result::fail()` into exceptions for transaction rollback when using `thenUnsafe()`.

```php
DB::transaction(function () use ($dto, $meta) {
    return Result::ok($dto, $meta)
        ->thenUnsafe(new ValidateOrderAction)->throwIfFail()
        ->thenUnsafe(new PersistOrderAction)->throwIfFail()
        ->thenUnsafe(new ChargePaymentAction)->throwIfFail();
});
```

**Throwing behavior:**
- If error is `Throwable`, throws it directly
- Otherwise throws `RuntimeException` with error as message (or JSON-encoded if not a string)

**When to use:**
- Combine with `thenUnsafe()` when you want `Result::fail()` to also trigger transaction rollback
- Without `throwIfFail()`, a `Result::fail()` from an action won't throw — only exceptions trigger rollback

---

### Exception Pattern Matching & Handling

Two helpers provide explicit, class-aware exception handling on failed Results:

- `catchException(array $handlers, ?callable $fallback = null): Result` — chainable recovery on matching Throwable subclasses. Each handler may return a `Result` or a plain value (wrapped as success).
- `matchException(array $exceptionHandlers, callable $onSuccess, callable $onUnhandled): mixed` — match on exception classes and map to a final return value (similar to `match` but dispatches by exception type).

Use these when you want to handle specific exception types explicitly without scattering try/catch blocks throughout controllers or actions.

> ⚠️ **Handler Ordering:** Exception handlers use `instanceof` matching and are checked in array order. The **first matching handler wins**. Always order handlers from most specific to least specific (child classes before parent classes).
>
> ```php
> // ✅ Correct: specific exceptions first
> ->catchException([
>     RuntimeException::class => fn($e) => ...,    // More specific
>     Exception::class => fn($e) => ...,           // Less specific (parent)
> ])
>
> // ❌ Wrong: parent class catches everything
> ->catchException([
>     Exception::class => fn($e) => ...,           // Catches ALL exceptions!
>     RuntimeException::class => fn($e) => ...,    // Never reached
> ])
> ```

```php
// Recover from specific exceptions
$result = Result::of(fn () => $service->run($dto))
    ->catchException([
        ValidationException::class => fn (ValidationException $e, array $meta) => Result::fail(['type' => 'validation', 'errors' => $e->errors()]),
        PaymentDeclined::class => fn (PaymentDeclined $e, array $meta) => Result::ok(['requires_3ds' => true], $meta),
    ], fallback: fn ($err, $meta) => Result::fail($err, $meta));
```

```php
// Controller boundary example using matchException
return Result::of(fn () => $service->run($dto))
    ->matchException(
        [
            ValidationException::class => fn (ValidationException $e) => response()->json(['error' => 'validation', 'details' => $e->errors()], 422),
            AuthenticationException::class => fn () => response()->json(['error' => 'unauthenticated'], 401),
        ],
        onSuccess: fn ($value) => response()->json(['data' => $value]),
        onUnhandled: fn ($error) => (report($error), response()->json(['error' => 'internal'], 500)),
    );
```

### Combining Results

#### `Result::combine(array $results): Result`

Aggregates multiple results. Short-circuits on first failure.

```php
$nameResult = (new ValidateName)($data['name']);
$emailResult = (new ValidateEmail)($data['email']);
$ageResult = (new ValidateAge)($data['age']);

$result = Result::combine([$nameResult, $emailResult, $ageResult])
    ->then(fn([$name, $email, $age]) => User::create([
        'name' => $name,
        'email' => $email,
        'age' => $age,
    ]));
```

#### `Result::combineAll(array $results): Result`

Aggregates multiple results, collecting ALL errors.

```php
$results = [
    Result::fail('Name is required'),
    Result::ok('valid@example.com'),
    Result::fail('Age must be 18+'),
];

$combined = Result::combineAll($results);
// Result::fail(['Name is required', 'Age must be 18+'])

// Perfect for form validation
$combined->onFailure(fn($errors) =>
    back()->withErrors(array_combine(['name', 'email', 'age'], $errors))
);
```

---

## Usage Patterns

### Pattern 1: Action Pipeline

```php
$result = (new CreateProduct)($dto)
    ->onSuccess(fn($p, $m) => Log::info('product.created', ['id' => $p->id]))
    ->then(new IndexProductInSearch)
    ->then(fn($p) => SendEmail::dispatch($p->id))
    ->onFailure(fn($e, $m) => Log::error('pipeline.failed', [
        'error' => (string) $e,
        'failed_step' => $m['failed_step'] ?? null,
    ]));
```

### Pattern 2: Controller Boundary with Match

```php
public function store(StoreProductRequest $request)
{
    return Result::ok($request->validated())
        ->then(new CreateProduct)
        ->then(new IndexProductInSearch)
        ->match(
            onSuccess: fn($product) => to_route('products.show', $product),
            onFailure: fn($error) => back()->withErrors(['error' => (string) $error]),
        );
}
```

### Pattern 2b: Controller Boundary with Exception Matching

```php
public function store(StoreProductRequest $request)
{
    return Result::ok($request->validated())
        ->then(new CreateProduct)
        ->matchException(
            [
                ValidationException::class => fn (ValidationException $e) => back()->withErrors($e->errors()),
                AuthenticationException::class => fn () => to_route('login'),
            ],
            onSuccess: fn ($product) => to_route('products.show', $product),
            onUnhandled: fn ($error) => back()->withErrors(['error' => (string) $error]),
        );
}
```

### Pattern 3: Validation Pipeline with Ensure

```php
$result = Result::ok($user)
    ->ensure(fn($u) => $u->isActive(), 'Account is deactivated')
    ->ensure(fn($u) => $u->hasVerifiedEmail(), 'Email not verified')
    ->ensure(fn($u) => ! $u->isBanned(), fn($u) => "Account banned: {$u->ban_reason}")
    ->then(new AuthorizeAction);
```

### Pattern 4: Conditional Recovery

```php
$result = (new ChargeCard)($payload)
    ->otherwise(function ($error, $meta) {
        if ($error instanceof Requires3DS) {
            return Result::ok(['requires_3ds' => true], $meta);
        }
        return Result::fail($error, $meta);
    });
```

### Pattern 4b: Exception-aware Recovery with catchException

```php
$result = Result::of(fn () => $gateway->charge($dto))
    ->catchException([
        Requires3DS::class => fn ($e, $m) => Result::ok(['requires_3ds' => true], $m),
        PaymentDeclined::class => fn ($e, $m) => Result::fail(['code' => 'payment_declined', 'message' => $e->getMessage()], $m),
    ], fallback: fn ($err, $m) => Result::fail($err, $m));
```

### Pattern 5: Batch Validation

```php
$results = [
    (new ValidateName)($data['name']),
    (new ValidateEmail)($data['email']),
    (new ValidatePassword)($data['password']),
];

return Result::combineAll($results)->match(
    onSuccess: fn($values) => User::create(array_combine(['name', 'email', 'password'], $values)),
    onFailure: fn($errors) => back()->withErrors($errors),
);
```

### Pattern 6: Data Transformation

```php
$result = Result::ok(['price_cents' => 1299], ['currency' => 'ZAR'])
    ->map(fn($v) => [...$v, 'price' => $v['price_cents'] / 100]);
```

### Pattern 7: Error Enrichment

```php
$result = $doSomething()
    ->mapError(fn($e, $meta) => [
        'message' => (string) $e,
        'code' => $e->getCode(),
        'timestamp' => now(),
        'context' => $meta,
    ]);
```

### Pattern 8: Using Handle Method

```php
final class IndexProductInSearch
{
    public function handle(Product $product, array $meta): Result
    {
        Search::index($product);
        return Result::ok($product, [...$meta, 'indexed' => true]);
    }
}

$result = Result::ok($dto)
    ->then(new CreateProduct)
    ->then(new IndexProductInSearch);
```

### Pattern 9: Custom Exception Throwing

```php
public function getUser(int $id): User
{
    return $this->userRepository->find($id)
        ->getOrThrow(fn($e) => new UserNotFoundException("User {$id} not found"));
}
```

---

## Anti-Patterns

### ❌ Anti-Pattern 1: Early Invocation

**Wrong:**
```php
->then((new NotifyAction)($dto))   // ❌ Invokes BEFORE then() runs
```

This executes `NotifyAction` immediately, outside the chain. Exceptions bypass the Result and bubble up to the framework.

**Correct:**
```php
->then(new NotifyAction)                                    // ✅ Pass the object
->then(fn($v, $m) => (new NotifyAction)($v, $m))           // ✅ Wrap in closure
->then(fn($v, $m) => Result::of(fn() => risky($v)))        // ✅ Wrap risky calls
```

### ❌ Anti-Pattern 2: Ignoring Failures

**Wrong:**
```php
$result = $pipeline->...;
$value = $result->value();  // ❌ Could be null if failed!
doSomething($value);
```

**Correct:**
```php
// Use match() for exhaustive handling
$result->match(
    onSuccess: fn($v) => doSomething($v),
    onFailure: fn($e) => handleError($e),
);

// Or check explicitly
if ($result->isOk()) {
    doSomething($result->value());
}

// Or use unwrapOr for safe defaults
$value = $result->unwrapOr($default);
```

### ❌ Anti-Pattern 3: Overly Broad Recovery

**Wrong:**
```php
->otherwise(fn($e) => Result::ok($default))  // ❌ Recovers from ALL errors
```

**Correct:**
```php
->otherwise(function ($error) use ($default) {
    if ($error instanceof NotFoundError) {
        return Result::ok($default);  // ✅ Specific recovery
    }
    return Result::fail($error);      // ✅ Let other errors propagate
})
```

### ❌ Anti-Pattern 4: Returning Raw Values from Fallible Steps

**Wrong:**
```php
->then(function ($data) {
    if (invalid($data)) {
        return null;  // ❌ Ambiguous - is null valid or failure?
    }
    return process($data);
})
```

**Correct:**
```php
->then(function ($data) {
    if (invalid($data)) {
        return Result::fail('Invalid data');  // ✅ Explicit failure
    }
    return Result::ok(process($data));        // ✅ Explicit success
})

// Or use ensure() for validation
->ensure(fn($data) => valid($data), 'Invalid data')
->map(fn($data) => process($data))
```

### ❌ Anti-Pattern 5: Losing Metadata

**Wrong:**
```php
->then(function ($value, $meta) {
    return Result::ok(transform($value));  // ❌ Meta is lost!
})
```

**Correct:**
```php
->then(function ($value, $meta) {
    return Result::ok(transform($value), $meta);  // ✅ Preserve meta
})

// Or add to it:
->then(function ($value, $meta) {
    return Result::ok(transform($value), [...$meta, 'transformed' => true]);
})
```

### ❌ Anti-Pattern 6: Mutating Meta Directly

**Wrong:**
```php
->then(function ($value, $meta) {
    $meta['events'][] = 'processed';  // ❌ Local mutation, doesn't persist
    return $value;
})
```

**Correct:**
```php
->then(function ($value, $meta) {
    $meta['events'][] = 'processed';
    return Result::ok($value, $meta);  // ✅ Return new Result with updated meta
})

// Or use mergeMeta:
->mergeMeta(['processed' => true])
```

### ❌ Anti-Pattern 7: Using combine() When You Need All Errors

**Wrong:**
```php
// Stops at first error - user only sees one validation message
Result::combine([
    $this->validateName($data),
    $this->validateEmail($data),
    $this->validatePassword($data),
]);
```

**Correct:**
```php
// Collects all errors - user sees all validation messages
Result::combineAll([
    $this->validateName($data),
    $this->validateEmail($data),
    $this->validatePassword($data),
]);
```

### ❌ Anti-Pattern 8: Wrong Handler Order in catchException/matchException

**Wrong:**
```php
// Parent class catches everything - child handlers never run
->catchException([
    \Exception::class => fn($e) => Result::fail('generic'),
    \InvalidArgumentException::class => fn($e) => Result::fail('validation'),  // Never reached!
])
```

**Correct:**
```php
// Order from most specific to least specific
->catchException([
    \InvalidArgumentException::class => fn($e) => Result::fail('validation'),  // Specific first
    \RuntimeException::class => fn($e) => Result::fail('runtime'),
    \Exception::class => fn($e) => Result::fail('generic'),                     // Catch-all last
])
```

---

## Internal Architecture

### How Chaining Works

The `runChain` method handles all pipeline execution:

```
┌─────────────────────────────────────────────────────────────┐
│                        runChain()                           │
├─────────────────────────────────────────────────────────────┤
│  1. Normalize input (single step → array of steps)          │
│  2. For each step:                                          │
│     a. Try to invoke the step                               │
│     b. If exception → return Result::fail(exception)        │
│     c. If returns Result:                                   │
│        - Update meta from result (propagation fix)          │
│        - If fail → return immediately (short-circuit)       │
│        - If ok → use value for next step                    │
│     d. If returns raw value → wrap as Result::ok()          │
│  3. Return final accumulated Result                         │
└─────────────────────────────────────────────────────────────┘
```

### Step Resolution

The `invokeStep` method supports multiple invocation patterns:

```
┌───────────────────────────────────────────────────────────┐
│                     invokeStep()                          │
├───────────────────────────────────────────────────────────┤
│  1. is_callable($step)?                                   │
│     → $step($arg, $meta)     // Closure or __invoke       │
│                                                           │
│  2. method_exists($step, 'handle')?                       │
│     → $step->handle($arg, $meta)                          │
│                                                           │
│  3. method_exists($step, 'execute')?                      │
│     → $step->execute($arg, $meta)                         │
│                                                           │
│  4. Otherwise → InvalidArgumentException                  │
└───────────────────────────────────────────────────────────┘
```

**Step Invocation Priority:**

When a step object implements multiple invocation styles, the pipeline uses this priority order:

1. **`is_callable()` check first** — This includes closures and objects with `__invoke()`
2. **`handle()` method** — If not callable, checks for `handle($value, $meta)`
3. **`execute()` method** — Falls back to `execute($value, $meta)`
4. **`InvalidArgumentException`** — Thrown if none of the above are available

> **Note:** Since `is_callable()` returns `true` for objects with `__invoke()`, an object with both `__invoke()` and `handle()` will always use `__invoke()`.

### Exception Safety

All steps within `then()` and `otherwise()` are wrapped in try/catch:

```php
try {
    $out = $this->invokeStep($step, $current, $meta);
} catch (Throwable $e) {
    return self::fail($e, $meta + ['failed_step' => $this->stepName($step)]);
}
```

This ensures:
- Exceptions don't escape the chain
- `onFailure()` handlers always run
- The `failed_step` meta key identifies where the failure occurred. Typical values recorded by `stepName()` include:
    - `closure` for anonymous / callable steps
    - The class name for object steps (e.g. `App\Actions\MyAction`)
    - `ClassName::method` for [class, 'method'] step tuples

> **Note:** The `failed_step` key uses array union (`$meta + ['failed_step' => ...]`), meaning the **first failure wins**. If a previous step already set `failed_step` in the metadata, it will be preserved. This ensures you always see where the chain originally failed.

> Note: The new `catchException()` and `matchException()` helpers sit on top of this behavior — they let you handle specific Throwable subclasses once the chain has converted exceptions to failures.

### Metadata Propagation

When a step returns a Result with updated metadata, that metadata is passed to subsequent steps:

```php
// Step 1 adds 'step1' to meta
->then(fn($v, $m) => Result::ok($v, [...$m, 'step1' => true]))
// Step 2 receives meta with 'step1' key
->then(fn($v, $m) => Result::ok($v, [...$m, 'step2' => true]))
// Final result has both keys
```

---

## Type Safety

### PHPStan/Psalm Templates

The Result uses generics for type inference:

```php
/**
 * @template TSuccess The success payload type
 * @template TFailure The failure payload type
 */
final class Result
```

### Type Transformations

| Method                  | Input Type            | Output Type                  |
| ----------------------- | --------------------- | ---------------------------- |
| `map(fn($v) => U)`      | `Result<T, E>`        | `Result<U, E>`               |
| `mapError(fn($e) => F)` | `Result<T, E>`        | `Result<T, F>`               |
| `ensure(pred, err)`     | `Result<T, E>`        | `Result<T, E>`               |
| `then(Action)`          | `Result<T, E>`        | `Result<U, E>`               |
| `flatMap(fn)`           | `Result<T, E>`        | `Result<U, E>`               |
| `otherwise(Handler)`    | `Result<T, E>`        | `Result<T, F>`               |
| `recover(fn($e) => U)`  | `Result<T, E>`        | `Result<T|U, never>`         |
| `match(onOk, onFail)`   | `Result<T, E>`        | `R`                          |
| `combine([...])`        | `array<Result<T, E>>` | `Result<array<T>, E>`        |
| `combineAll([...])`     | `array<Result<T, E>>` | `Result<array<T>, array<E>>` |

### Typed Actions

For best type inference, use concrete types in action signatures:

```php
final class CreateProduct
{
    public function __invoke(ProductDTO $dto, array $meta): Result
    {
        // PHPStan knows $dto is ProductDTO
        $product = Product::create($dto->toArray());
        return Result::ok($product, $meta);
    }
}
```

---

## Testing

### Basic State Tests

```php
it('creates success result', function () {
    $result = Result::ok('value');
    
    expect($result->isOk())->toBeTrue();
    expect($result->isFail())->toBeFalse();
    expect($result->value())->toBe('value');
    expect($result->error())->toBeNull();
});

it('creates failure result', function () {
    $result = Result::fail('error');
    
    expect($result->isOk())->toBeFalse();
    expect($result->isFail())->toBeTrue();
    expect($result->value())->toBeNull();
    expect($result->error())->toBe('error');
});
```

### Chaining Tests

```php
it('chains successful operations', function () {
    $result = Result::ok(5)
        ->map(fn($v) => $v * 2)
        ->map(fn($v) => $v + 1);
    
    expect($result->unwrap())->toBe(11);
});

it('short-circuits on failure', function () {
    $executed = false;
    
    $result = Result::fail('error')
        ->then(function () use (&$executed) {
            $executed = true;
            return Result::ok('value');
        });
    
    expect($executed)->toBeFalse();
    expect($result->isFail())->toBeTrue();
});
```

### Ensure Tests

```php
it('passes when predicate is true', function () {
    $result = Result::ok(10)
        ->ensure(fn($v) => $v > 5, 'Too small');
    
    expect($result->isOk())->toBeTrue();
    expect($result->value())->toBe(10);
});

it('fails when predicate is false', function () {
    $result = Result::ok(3)
        ->ensure(fn($v) => $v > 5, 'Too small');
    
    expect($result->isFail())->toBeTrue();
    expect($result->error())->toBe('Too small');
});
```

### Combine Tests

```php
it('combines successful results', function () {
    $results = [
        Result::ok('a'),
        Result::ok('b'),
        Result::ok('c'),
    ];
    
    $combined = Result::combine($results);
    
    expect($combined->isOk())->toBeTrue();
    expect($combined->value())->toBe(['a', 'b', 'c']);
});

it('combineAll collects all errors', function () {
    $results = [
        Result::fail('error1'),
        Result::ok('value'),
        Result::fail('error2'),
    ];
    
    $combined = Result::combineAll($results);
    
    expect($combined->isFail())->toBeTrue();
    expect($combined->error())->toBe(['error1', 'error2']);
});
```

### Match Tests

```php
it('matches success branch', function () {
    $result = Result::ok('hello')
        ->match(
            onSuccess: fn($v) => strtoupper($v),
            onFailure: fn($e) => 'ERROR',
        );
    
    expect($result)->toBe('HELLO');
});

it('matches failure branch', function () {
    $result = Result::fail('oops')
        ->match(
            onSuccess: fn($v) => 'SUCCESS',
            onFailure: fn($e) => "Error: {$e}",
        );
    
    expect($result)->toBe('Error: oops');
});
```

### Exception Safety Tests

```php
it('converts exceptions to failures', function () {
    $result = Result::ok('x')
        ->then(fn() => throw new RuntimeException('boom'));
    
    expect($result->isFail())->toBeTrue();
    expect($result->error())->toBeInstanceOf(RuntimeException::class);
    expect($result->meta()['failed_step'])->toBe('closure');
});
```

### Recovery Tests

```php
it('recovers from failure', function () {
    $result = Result::fail('error')
        ->otherwise(fn() => Result::ok('recovered'));
    
    expect($result->isOk())->toBeTrue();
    expect($result->value())->toBe('recovered');
});

it('skips otherwise on success', function () {
    $executed = false;
    
    Result::ok('value')
        ->otherwise(function () use (&$executed) {
            $executed = true;
            return Result::ok('other');
        });
    
    expect($executed)->toBeFalse();
});
```

---

## Quick Reference

### Static Constructors

```php
Result::ok($value)                          // Success
Result::ok($value, ['key' => 'value'])      // Success with meta
Result::fail($error)                        // Failure
Result::fail($error, ['key' => 'value'])    // Failure with meta
Result::failWithValue($error, $input)       // Failure preserving input
Result::of(fn() => riskyOperation())        // Wrap callable
Result::combine([$r1, $r2, $r3])            // Combine, fail-fast
Result::combineAll([$r1, $r2, $r3])         // Combine, collect all errors
```

### Checking & Accessing

```php
$result->isOk()          // bool
$result->isFail()        // bool
$result->value()         // TSuccess|null
$result->error()         // TFailure|null
$result->meta()          // array
$result->toArray()       // Full array representation
$result->toDebugArray()  // Safe debug representation (optional sanitizer)
```

### Transforming

```php
->map(fn($v, $m) => transform($v))          // Transform success value
->mapError(fn($e, $m) => transform($e))     // Transform error value
->ensure(fn($v) => valid($v), 'error')      // Validate with predicate
->then(new Action)                          // Chain on success (catches exceptions)
->thenUnsafe(new Action)                    // Chain on success (exceptions bubble)
->flatMap(fn($v) => $repo->find($v))        // Alias for then()
->otherwise(new Handler)                    // Chain on failure
->recover(fn($e, $m) => $default)           // Failure → Success
```

### Side Effects

```php
->tap(fn($v, $e, $m) => ...)                // Observe both branches
->onSuccess(fn($v, $m) => ...)              // Observe success
->inspect(fn($v, $m) => ...)                // Alias for onSuccess
->onFailure(fn($e, $m) => ...)              // Observe failure
->inspectError(fn($e, $m) => ...)           // Alias for onFailure
->tapMeta(fn($m) => ...)                    // Observe meta
```

### Metadata

```php
->mergeMeta(['key' => 'value'])             // Add to meta
->mapMeta(fn($m) => transform($m))          // Replace meta
```

### Pattern Matching & Unwrapping

```php
->match(onSuccess: fn($v) => ..., onFailure: fn($e) => ...)  // Exhaustive handling
->catchException([...], fallback: fn($e, $m) => ...) // Match failure Throwable classes and recover
->matchException([...], onSuccess: fn($v) => ..., onUnhandled: fn($e) => ...) // Match on exception classes and return a mapped value
```

### Action Signatures

```php
// Any of these work with then()/otherwise():
public function __invoke($input, array $meta): Result
public function handle($input, array $meta): Result
public function execute($input, array $meta): Result
```

---

## Testing

```bash
composer test
```
