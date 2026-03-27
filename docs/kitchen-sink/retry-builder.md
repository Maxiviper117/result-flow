---
title: Retry Builder
---

# Retry Builder

This page documents the fluent retry builder returned by `Result::retrier()` and provides concise, practical end-to-end examples you can copy-and-run. The builder is a small, synchronous retry helper that converts plain return values into `Result` instances, converts thrown exceptions into failure results, and retries failures according to your configuration.

Use the builder when you have an operation that may fail transiently (network requests, rate limits, temporary downstream errors) and you want a straightforward, testable retry loop.

## Quick Map

| Function            | What it does                                    |
| ------------------- | ----------------------------------------------- |
| `maxAttempts`       | Sets the retry limit                            |
| `delay`             | Sets the base delay between attempts            |
| `exponential`       | Enables exponential backoff                     |
| `jitter`            | Adds random jitter                              |
| `attachAttemptMeta` | Adds retry attempt metadata to the final result |
| `when`              | Adds a retry predicate                          |
| `onRetry`           | Adds a callback before each retry               |
| `attempt`           | Runs the retry loop                             |

## maxAttempts

`maxAttempts(...)` sets the maximum number of attempts.

```php
maxAttempts(int $times): self
```

The builder clamps the value to at least 1.

Use it to keep retry budgets explicit and bounded.

Use:

```php
$builder = Result::retrier()->maxAttempts(5);
```

## delay

`delay(...)` sets the base delay between attempts in milliseconds.

```php
delay(int $ms): self
```

The builder clamps the value to at least 0.

Use it when you want a fixed wait between retries.

Use:

```php
$builder = Result::retrier()->delay(100);
```

## exponential

`exponential(...)` enables or disables exponential backoff.

```php
exponential(bool $enabled = true): self
```

Use it when later attempts should wait longer than earlier ones.

Use:

```php
$builder = Result::retrier()->exponential(true);
```

## jitter

`jitter(...)` adds random jitter up to the given number of milliseconds.

```php
jitter(int $ms): self
```

Use it when you want to avoid retry storms or synchronized retries.

Use:

```php
$builder = Result::retrier()->jitter(50);
```

## attachAttemptMeta

`attachAttemptMeta(...)` adds retry metadata to the final result.

```php
attachAttemptMeta(bool $enable = true): self
```

When enabled, the builder writes `meta['retry']['attempts']` with the attempt count.

Use it when callers need to inspect or log retry effort.

Use:

```php
$builder = Result::retrier()->attachAttemptMeta();
```

## when

`when(...)` sets a predicate that decides whether the builder should retry after a failure.

```php
when(callable $predicate): self
```

The predicate receives the last error and the current attempt number.

Use it when only transient failures should be retried.

Use:

```php
$builder = Result::retrier()->when(fn ($error, int $attempt) => $attempt < 3);
```

## onRetry

`onRetry(...)` registers a callback that runs before each retry.

```php
onRetry(callable $callback): self
```

The callback receives the attempt number, the last error, and the computed wait time.

Use it for logging, metrics, or debugging retry behavior.

Use:

```php
$builder = Result::retrier()->onRetry(
    fn (int $attempt, $error, int $waitMs) => logger()->warning('retrying', compact('attempt', 'waitMs')),
);
```

## attempt

`attempt(...)` runs the retry loop.

```php
attempt(callable $fn): Result
```

### Inputs:

* `$fn`: callback that may return a plain value or a `Result`

### Behavior:

- plain return values become success
- successful `Result` values are returned as-is
- failure `Result` values may be retried
- thrown exceptions become failure results

Use it as the execution step after configuring the builder.

Use:

```php
$result = Result::retrier()
    ->maxAttempts(3)
    ->delay(100)
    ->attempt(fn () => callApi());
```

## Quick start

A minimal, copy-paste example that retries a failing closure up to 3 times with a 100ms base delay:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::retrier()
    ->maxAttempts(3)
    ->delay(100)
    ->attempt(function () {
        // This may return a plain value, a Result, or throw.
        return callApi();
    });

if ($result->isOk()) {
    echo "ok: ", (string) $result->unwrap(), PHP_EOL;
} else {
    echo "failed after attempts=", ($result->meta()['retry']['attempts'] ?? 0), PHP_EOL;
}
```

## Practical examples (end-to-end)

These examples show common real-world usages and patterns. They are synchronous, self-contained, and intentionally explicit so you can adapt them.

### Example A — retry an operation that throws until success

Simulate an unstable API that throws on the first two attempts and succeeds on the third. The retrier converts exceptions into failure results and retries them.

```php
use Maxiviper117\ResultFlow\Result;

function unstableApi(): string
{
    static $n = 0;
    $n++;
    if ($n < 3) {
        throw new RuntimeException('temporary');
    }
    return 'response-' . $n;
}

$res = Result::retrier()
    ->maxAttempts(5)
    ->delay(50)
    ->attempt(fn () => unstableApi());

// success after retries
if ($res->isOk()) {
    echo $res->unwrap(); // response-3
}
```

### Example B — retry only on specific failures using `when()` and mixing `Result`

Here we return `Result::fail()` for certain errors and only retry when the predicate allows it (e.g., transient HTTP 503).

```php
use Maxiviper117\ResultFlow\Result;

$callCount = 0;
$res = Result::retrier()
    ->maxAttempts(4)
    ->delay(100)
    ->when(fn ($error, int $attempt) => str_contains((string) $error, '503') && $attempt < 4)
    ->attempt(function () use (&$callCount) {
        $callCount++;
        // Simulate returning a Result for HTTP responses
        if ($callCount < 3) {
            return Result::fail('503 Service Unavailable');
        }
        return Result::ok('payload-' . $callCount);
    });

if ($res->isOk()) {
    echo $res->unwrap(); // payload-3
}
```

### Example C — exponential backoff, jitter, and `onRetry` logging

Use exponential backoff and jitter to spread retries across callers and avoid synchronized retry storms. `onRetry` receives (attempt, lastError, waitMs).

```php
use Maxiviper117\ResultFlow\Result;

$res = Result::retrier()
    ->maxAttempts(6)
    ->delay(100)        // base 100ms
    ->exponential(true) // 100, 200, 400, ...
    ->jitter(80)        // +/- up to 80ms of random jitter
    ->onRetry(fn (int $attempt, $error, int $waitMs) =>
        error_log("retry #{$attempt} after {$waitMs}ms: {$error}")
    )
    ->attempt(fn () => callUnreliableRemote());

if ($res->isOk()) {
    // handle success
} else {
    // handle final failure
}
```

### Example D — attach attempt metadata and inspect attempts on final result

If you want observability, enable `attachAttemptMeta()` so the final `Result` includes the number of attempts under `meta['retry']['attempts']`.

```php
use Maxiviper117\ResultFlow\Result;

$res = Result::retrier()
    ->maxAttempts(3)
    ->delay(50)
    ->attachAttemptMeta(true)
    ->attempt(fn () => Result::fail('always-bad'));

if ($res->isErr()) {
    $attempts = $res->meta()['retry']['attempts'] ?? 0; // 3
    echo "failed after {$attempts} attempts\n";
}
```

## Notes and best practices

- Keep `maxAttempts()` bounded and explicit; retries are not free.
- Prefer `when()` for predicate-driven retries (only retry transient errors).
- Use `exponential()` + `jitter()` for distributed systems to avoid thundering herds.
- Use `attachAttemptMeta()` when you need telemetry or logs showing effort.
- `onRetry()` is useful for metrics, but keep callbacks lightweight and side-effect free.
## See Also

- [Construction and entry points](./construction)
- [Kitchen sink overview](./)
## See Also

- [Construction and entry points](./construction)
- [Kitchen sink overview](./)
