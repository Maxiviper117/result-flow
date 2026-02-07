---
title: Constructing results
---

# Constructing results

This page explains how to create `Result` instances and combine them. It also covers how values, errors, and metadata are stored so you can reason about pipelines with confidence.

## The Result shape

A `Result` is always in exactly one of two states:

```
Ok(value, meta) | Fail(error, meta)
```

Key points:
- `value()` is only meaningful on success; on failure it returns `null`.
- `error()` is only meaningful on failure; on success it returns `null`.
- `meta()` is always available and travels through the pipeline.

## Static constructors

### `Result::ok()` and `Result::fail()`

```php
use Maxiviper117\ResultFlow\Result;

// Success with metadata
$loaded = Result::ok($payload, ['request_id' => $rid]);

// Failure with a domain error object
$failed = Result::fail(new DomainError('Payment declined'), ['attempt' => 2]);
```

Notes:
- `ok()` stores the value in the success channel and leaves the error channel empty.
- `fail()` stores the error in the failure channel and leaves the value empty.
- Both accept metadata, which is carried forward through the chain.

### `Result::failWithValue()` keeps the triggering value

Useful for validation failures where you want to keep the rejected input close to the error.

```php
$payload = ['email' => 'not-an-email'];

$invalid = Result::failWithValue('Invalid email', $payload, ['source' => 'signup']);

$invalid->meta();
// ['failed_value' => ['email' => 'not-an-email'], 'source' => 'signup']
```

Notes:
- `failed_value` is added into metadata automatically.
- If you pass `failed_value` in the meta array, your value will override the default.

### `Result::of()` wraps exceptions automatically

Any exception is converted into a failure so you can keep chaining without try/catch noise.

```php
$userResult = Result::of(fn () => $userRepo->findOrFail($id))
    ->map(fn ($user) => $user->profile());

if ($userResult->isFail()) {
    // error() returns the original Throwable for logging or matching
    logger()->warning('Profile lookup failed', ['error' => $userResult->error()]);
}
```

`of()` only captures exceptions. If the callable returns normally, the value becomes `Result::ok($value)`.

## Combining many results

### `Result::combine()` short-circuits on the first failure

```php
$combined = Result::combine([
    Result::ok(loadUser($id)),
    Result::ok(loadAccount($id)),
    Result::ok(loadPreferences($id)),
]);

return $combined->match(
    onSuccess: fn (array $values) => hydrateDashboard(...$values),
    onFailure: fn ($error) => response()->json(['error' => $error], 400),
);
```

Behavior details:
- Stops at the first failure and returns that error.
- Metadata from all processed results is merged (later keys win).
- If all are ok, the value is an array of the success values in input order.

### `Result::combineAll()` collects all errors

Use this when you want full visibility into multiple failures instead of failing fast.

```php
$checks = Result::combineAll([
    validateEmail($input['email']),
    validatePassword($input['password']),
    validateProfile($input['profile'] ?? []),
]);

$checks->match(
    onSuccess: fn ($values) => persistAll($values),
    onFailure: fn (array $errors) => report_all($errors),
);
```

Behavior details:
- Collects all errors into an array (order matches the input list).
- If there are any errors, the result is `fail(array<E>)`.
- Metadata from all results is merged (later keys win).

### Merging metadata from combined results

Both `combine()` and `combineAll()` merge metadata from each input in order. On key conflicts, the later value wins.

```php
$steps = Result::combine([
    Result::ok($dto, ['step' => 'validated']),
    Result::ok(enrich($dto), ['step' => 'enriched']),
]);

$meta = $steps->meta();
// ['step' => 'enriched']
```

## Mapping item collections

### `Result::mapItems()` for per-item outcomes

When each item in a collection has its own `Result` flow, `mapItems()` removes manual loops and preserves keys.

```php
$rows = ['row-1' => $payload1, 'row-2' => $payload2];

$mapped = Result::mapItems(
    $rows,
    fn (array $row, string $key) => importRow($row, ['row_key' => $key]),
);

$mapped['row-1']->isOk();
$mapped['row-2']->isFail();
```

Key points:
- Callback signature: `fn ($item, $key) => Result|value`.
- Plain callback values are wrapped as `Result::ok(...)`.
- Thrown exceptions are captured as `Result::fail(Throwable)` for that item.
- Return type is `array<key, Result<...>>`, so you can inspect each item independently.

### `Result::mapAll()` for fail-fast batch processing

`mapAll()` short-circuits on the first mapped failure and returns that error.

```php
$result = Result::mapAll(
    ['a' => 1, 'b' => 2, 'c' => 3],
    fn (int $value) => $value > 1 ? Result::ok($value * 10) : Result::fail('too-small'),
);
```

Key points:
- Stops processing immediately when the first item fails.
- Success shape: `Result::ok(array<key, mappedValue>)`.
- Failure shape: `Result::fail(firstError)`.
- Metadata merges from processed results only, in order (later keys overwrite earlier keys).
- On failure, `value()` is `null` (partial successes are not returned in the value channel).

### `Result::mapCollectErrors()` for full error reporting

`mapCollectErrors()` keeps processing every item and returns all failures keyed by their original input keys.

```php
$result = Result::mapCollectErrors(
    ['email' => $email, 'password' => $password],
    fn (mixed $value, string $field) => validateField($field, $value),
);

$result->match(
    onSuccess: fn (array $values) => persistValidated($values),
    onFailure: fn (array $errors) => report_all($errors),
);
```

Key points:
- Never short-circuits; every item is evaluated.
- Success shape: `Result::ok(array<key, mappedValue>)`.
- Failure shape: `Result::fail(array<key, error>)`.
- Error keys map directly to the input keys that failed.
- Metadata merges from all mapped results, in order (later keys overwrite earlier keys).
- On failure, `value()` is `null`.

### Choosing between the three

- Choose `mapItems()` when downstream logic needs item-by-item `Result` inspection.
- Choose `mapAll()` when the first failure should stop work immediately.
- Choose `mapCollectErrors()` for validations/imports where complete error reporting matters more than fail-fast execution.
