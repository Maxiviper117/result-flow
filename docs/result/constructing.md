---
title: Constructing results
---

# Constructing results

This page shows how to create `Result` instances and combine them. Each example highlights how metadata is preserved and how exceptions are captured.

## Static constructors

### `Result::ok()` and `Result::fail()`

```php
use Maxiviper117\ResultFlow\Result;

// Success with metadata
$loaded = Result::ok($payload, ['request_id' => $rid]);

// Failure with a domain error object
$failed = Result::fail(new DomainError('Payment declined'), ['attempt' => 2]);
```

### `Result::failWithValue()` keeps the triggering value

Useful for validation failures where you want to keep the rejected input close to the error.

```php
$payload = ['email' => 'not-an-email'];

$invalid = Result::failWithValue('Invalid email', $payload);

$invalid->meta();
// ['failed_value' => ['email' => 'not-an-email']]
```

### `Result::of()` wraps exceptions automatically

Any exception is converted into a failure so you can keep chaining without `try/catch` noise.

```php
$userResult = Result::of(fn() => $userRepo->findOrFail($id))
    ->map(fn($user) => $user->profile());

if ($userResult->isFail()) {
    // error() returns the original Throwable for logging or matching
    logger()->warning('Profile lookup failed', ['error' => $userResult->error()]);
}
```

## Combining many results

### `Result::combine()` short-circuits on the first failure

```php
$combined = Result::combine([
    Result::ok(loadUser($id)),
    Result::ok(loadAccount($id)),
    Result::ok(loadPreferences($id)),
]);

return $combined->match(
    onSuccess: fn([$user, $account, $prefs]) => hydrateDashboard($user, $account, $prefs),
    onFailure: fn($error) => response()->json(['error' => $error], 400),
);
```

### `Result::combineAll()` collects *all* errors

Use this when you want full visibility into multiple failures (e.g., batch validation) instead of failing fast.

```php
$checks = Result::combineAll([
    validateEmail($input['email']),
    validatePassword($input['password']),
    validateProfile($input['profile'] ?? []),
]);

$checks->match(
    onSuccess: fn($values) => persistAll($values),
    onFailure: fn(array $errors) => report_all($errors),
);
```

### Merging metadata from combined results

Both `combine()` and `combineAll()` merge metadata from each input in order. You can store helpful context (like step names) on each partial result and retrieve it later.

```php
$steps = Result::combine([
    Result::ok($dto, ['step' => 'validated']),
    Result::ok(enrich($dto), ['step' => 'enriched']),
]);

$meta = $steps->meta();
// ['step' => 'enriched'] — last write wins on conflicting keys
```
