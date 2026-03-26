---
title: Retry Builder
---

# Retry Builder

This group covers the fluent retry builder returned by `Result::retrier()`.

## Quick Map

| Function | What it does |
| --- | --- |
| `maxAttempts` | Sets the retry limit |
| `delay` | Sets the base delay between attempts |
| `exponential` | Enables exponential backoff |
| `jitter` | Adds random jitter |
| `attachAttemptMeta` | Adds retry attempt metadata to the final result |
| `when` | Adds a retry predicate |
| `onRetry` | Adds a callback before each retry |
| `attempt` | Runs the retry loop |

## maxAttempts

`maxAttempts(...)` sets the maximum number of attempts.

The builder clamps the value to at least 1.

Use it to keep retry budgets explicit and bounded.

Shape:

```php
// builder with an updated maximum attempt count
```

Use:

```php
$builder = Result::retrier()->maxAttempts(5);
```

## delay

`delay(...)` sets the base delay between attempts in milliseconds.

The builder clamps the value to at least 0.

Use it when you want a fixed wait between retries.

Shape:

```php
// builder with an updated base delay
```

Use:

```php
$builder = Result::retrier()->delay(100);
```

## exponential

`exponential(...)` enables or disables exponential backoff.

Use it when later attempts should wait longer than earlier ones.

Shape:

```php
// builder with exponential backoff enabled or disabled
```

Use:

```php
$builder = Result::retrier()->exponential(true);
```

## jitter

`jitter(...)` adds random jitter up to the given number of milliseconds.

Use it when you want to avoid retry storms or synchronized retries.

Shape:

```php
// builder with jitter configured
```

Use:

```php
$builder = Result::retrier()->jitter(50);
```

## attachAttemptMeta

`attachAttemptMeta(...)` adds retry metadata to the final result.

When enabled, the builder writes `meta['retry']['attempts']` with the attempt count.

Use it when callers need to inspect or log retry effort.

Shape:

```php
// builder that adds meta['retry']['attempts'] to the final result
```

Use:

```php
$builder = Result::retrier()->attachAttemptMeta();
```

## when

`when(...)` sets a predicate that decides whether the builder should retry after a failure.

The predicate receives the last error and the current attempt number.

Use it when only transient failures should be retried.

Shape:

```php
// builder with a retry predicate
```

Use:

```php
$builder = Result::retrier()->when(fn ($error, int $attempt) => $attempt < 3);
```

## onRetry

`onRetry(...)` registers a callback that runs before each retry.

The callback receives the attempt number, the last error, and the computed wait time.

Use it for logging, metrics, or debugging retry behavior.

Shape:

```php
// builder with a retry callback
```

Use:

```php
$builder = Result::retrier()->onRetry(
    fn (int $attempt, $error, int $waitMs) => logger()->warning('retrying', compact('attempt', 'waitMs')),
);
```

## attempt

`attempt(...)` runs the retry loop.

Shape:

```php
// Ok($value, meta: [...]) on success
// or Fail($error, meta: [...]) on terminal failure
```

Use it as the execution step after configuring the builder.

Use:

```php
$result = Result::retrier()
    ->maxAttempts(3)
    ->delay(100)
    ->attempt(fn () => callApi());
```

## See Also

- [Construction and entry points](./construction)
- [Kitchen sink overview](./)
