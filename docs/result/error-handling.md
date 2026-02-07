---
title: Error Handling
---

# Error Handling

## What this page is for

Use this page to recover, transform, or route failures without breaking pipeline readability.

## `otherwise()`

`otherwise()` is the failure-branch counterpart to `then()`.

```php
$result = callService()
    ->otherwise(function ($error, array $meta) {
        if ($error === 'timeout') {
            return Result::ok(['source' => 'cache'], [...$meta, 'fallback' => true]);
        }

        return Result::fail($error, $meta);
    });
```

Behavior:
- Runs only when current result is failed.
- If callback returns `ok`, pipeline recovers.
- If callback returns plain value, it is wrapped as `ok(value)`.

## `catchException()`

Handle Throwable failures by class.

```php
$result = Result::of(fn () => riskyCall())
    ->catchException([
        RuntimeException::class => fn (RuntimeException $e) => Result::fail("runtime: {$e->getMessage()}"),
        InvalidArgumentException::class => fn (InvalidArgumentException $e) => Result::ok(['fallback' => true]),
    ]);
```

Behavior:
- Skips on success.
- Matches first handler whose class `is_a` the Throwable.
- Optional fallback handles unmatched failures.

## `recover()`

Convert any failure into success value.

```php
$alwaysOk = maybeFailingOp()
    ->recover(fn ($error) => ['fallback' => true, 'reason' => (string) $error]);
```

Behavior:
- If already success, returns original success value.
- If failure, executes callback and returns `ok(mappedValue)`.

## `throwIfFail()`

Escalate `Result` failure into exceptions.

```php
Result::ok($dto)
    ->thenUnsafe(new ValidateAction)
    ->thenUnsafe(new PersistAction)
    ->throwIfFail();
```

Behavior:
- If error is Throwable, throws it.
- If error is non-Throwable, throws `RuntimeException`.

## Choose this method when

| Need | Use |
|---|---|
| Branch on failure and maybe recover | `otherwise` |
| Match failure by exception class | `catchException` |
| Always convert failure to success | `recover` |
| Force exception semantics on fail | `throwIfFail` |

## Related pages

- [Chaining and Transforming](/result/chaining)
- [Matching and Unwrapping](/result/matching-unwrapping)
- [API Reference](/api)
