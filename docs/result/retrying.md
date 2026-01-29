# Retrying Operations

Transient failures like timeouts, database deadlocks, or flaky API calls are common. Result Flow provides a fluent retry mechanism that integrates directly with `Result`.

Instead of writing manual loops with try/catch, wrap the operation in a retrier that captures exceptions as `Result::fail` and can retry based on your rules.

## Basic retry

For simple cases, use the static `Result::retry` helper.

```php
use Maxiviper117\ResultFlow\Result;

// Try 3 times, with no delay between attempts
$result = Result::retry(3, fn () => $client->call());
```

You can also specify a fixed delay (in milliseconds) and exponential backoff:

```php
// Try 3 times, starting with 100ms delay, doubling each time
$result = Result::retry(3, fn () => $api->call(), delay: 100, exponential: true);
```

Notes:
- `times` is the maximum number of attempts (minimum 1).
- The first failure is attempt 1; retries happen after that failure.
- `retry()` is built on top of `Result::retrier()` and uses the same semantics.

## Advanced configuration

Use `Result::retrier()` for full control, including jitter, predicates, and callbacks.

```php
$result = Result::retrier()
    ->maxAttempts(5)        // Default: 1
    ->delay(200)            // Base delay in ms. Default: 0
    ->exponential()         // Delay = base * 2^(attempt-1)
    ->jitter(50)            // Add random jitter (0-50ms)
    ->onRetry(function (int $attempt, $error, int $wait) {
        // Called before each retry
        Log::warning("Attempt $attempt failed; waiting {$wait}ms");
    })
    ->when(function ($error, int $attempt) {
        // Only retry timeouts
        return $error instanceof TimeoutException;
    })
    ->attempt(fn () => $service->performAction());
```

What each hook does:
- `when($error, $attempt)`: decides whether to retry after a failure.
- `onRetry($attempt, $error, $wait)`: runs before waiting/sleeping.
- `jitter($ms)`: adds a random 0..$ms delay to avoid thundering herds.

## Works with Results or exceptions

`attempt()` handles both of these cases:

```php
// Functions returning Result
Result::retrier()->attempt(fn () => Result::fail('error'));

// Functions throwing exceptions
Result::retrier()->attempt(function () {
    throw new Exception('boom');
});
```

Behavior details:
- If the callable returns a `Result::ok`, retries stop and that result is returned.
- If it returns `Result::fail`, the error is used for retry decisions.
- If it returns a raw value, it is wrapped as `Result::ok($value)`.
- If it throws, the exception becomes the failure payload.

## Practical guidance

Retries are best for transient errors. Avoid retrying on validation failures or deterministic errors that will not succeed on another attempt. Use `when()` to make that distinction.
