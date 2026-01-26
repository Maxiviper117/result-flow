# Retrying Operations

Transient failures—like network timeouts, database deadlocks, or flaky API calls—are common in distributed systems. `ResultFlow` provides a built-in, fluent retry mechanism that integrates seamlessly with the `Result` type.

Instead of writing manual `try/catch` loops or relying on external libraries, you can wrap any operation in a robust retrier that captures exceptions as `Result::fail`.

## Basic Retry

For simple cases where you just want to try an operation a few times, use the static `Result::retry` helper.

```php
use Maxiviper117\ResultFlow\Result;

// Try 3 times, with no delay between attempts
$result = Result::retry(3, fn() => Http::get('https://api.example.com/data'));

if ($result->isOk()) {
    // Succeeded within 3 attempts
}
```

You can also specify a fixed delay (in milliseconds) and whether to use exponential backoff:

```php
// Try 3 times, starting with 100ms delay, doubling each time (100ms, 200ms)
$result = Result::retry(3, fn() => $api->call(), delay: 100, exponential: true);
```

## Advanced Configuration

For more control, use `Result::retrier()` to access the fluent builder. This allows you to configure jitter, custom retry predicates, and side-effect callbacks (e.g., for logging).

### Builder Options

```php
$result = Result::retrier()
    ->maxAttempts(5)        // Default: 1
    ->delay(200)            // Base delay in ms. Default: 0
    ->exponential()         // Enable exponential backoff (2^attempt * delay)
    ->jitter(50)            // Add random jitter (0-50ms) to spread out retries
    ->onRetry(function(int $attempt, $error, int $wait) {
        // Log the failure before waiting
        Log::warning("Attempt $attempt failed: " . $error->getMessage());
    })
    ->when(function($error, int $attempt) {
        // Only retry if it's a timeout, fail immediately for other errors
        return $error instanceof TimeoutException;
    })
    ->attempt(fn() => $service->performAction());
```

### Jitter

Adding "jitter" (randomness) to retry delays is a best practice to prevent "thundering herd" problems where many clients retry exactly at the same time.

```php
Result::retrier()
    ->delay(100)
    ->jitter(25) // Wait will be 100ms + random(0, 25)ms
    ->attempt(...);
```

### Conditional Retries

Use `when()` to decide whether to retry based on the error or the current attempt count. If the predicate returns `false`, the retrier stops immediately and returns the last failure.

```php
Result::retrier()
    ->when(function($error) {
        // Don't retry validation errors, they will never pass
        return !($error instanceof ValidationException);
    })
    ->attempt(...);
```

## How it Works with Results

The `attempt()` method automatically handles both exceptions and `Result` objects:

1. **Exceptions**: If the callable throws an exception, it is caught and treated as a failure.
2. **Result objects**: If the callable returns a `Result`, `isOk()` checks determine success.

```php
// Works with functions returning Result
Result::retrier()->attempt(fn() => Result::fail('error'));

// Works with functions throwing exceptions
Result::retrier()->attempt(function() {
    throw new Exception('boom');
});
```
