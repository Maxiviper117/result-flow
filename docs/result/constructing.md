---
title: Constructing Results
---

# Constructing Results

## What this page is for

Use this page when creating `Result` values, wrapping throwing code, or aggregating multiple `Result` instances.

## `ok()` and `fail()`

```php
use Maxiviper117\ResultFlow\Result;

$ok = Result::ok(['id' => 1], ['request_id' => 'r-1']);
$fail = Result::fail('Invalid input', ['field' => 'email']);
```

Behavior:
- `ok(value, meta)` sets success channel and clears error channel.
- `fail(error, meta)` sets failure channel and clears value channel.
- Metadata remains available through `meta()` regardless of branch.

## `failWithValue()`

```php
$invalid = Result::failWithValue('Invalid email', ['email' => 'bad'], ['source' => 'signup']);
```

Behavior:
- Adds `meta['failed_value']` automatically.
- Useful for validation and import diagnostics.

## `of()` for exception wrapping

```php
$userResult = Result::of(fn () => $repo->findOrFail($id));
```

Behavior:
- If callback returns normally: `Result::ok(returnValue)`.
- If callback throws: `Result::fail(Throwable)`.

## Aggregating existing `Result` values

### `combine()` (fail-fast)

```php
$combined = Result::combine([
    loadUser($id),
    loadAccount($id),
    loadPreferences($id),
]);
```

Behavior:
- Stops at first failure and returns that error.
- Success value is ordered array of success values.
- Metadata merges in processing order (later keys overwrite earlier keys).

### `combineAll()` (collect all errors)

```php
$checks = Result::combineAll([
    validateEmail($email),
    validatePassword($password),
    validateProfile($profile),
]);
```

Behavior:
- Evaluates all input results.
- If any fail: returns `fail(array<error>)`.
- If all pass: returns `ok(array<value>)`.

## When to use batch mapping instead

If you start from raw items (not prebuilt `Result` objects), use:
- `mapItems`
- `mapAll`
- `mapCollectErrors`

See [Batch Processing](/result/batch-processing).

## Related pages

- [Chaining and Transforming](/result/chaining)
- [Error Handling](/result/error-handling)
- [API Reference](/api)
