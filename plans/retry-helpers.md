# Plan: Retry Helpers for Transient Failures

## Goal
Add utility methods to handle transient failures (e.g., network requests, flaky services) with flexible retry logic, including a fluent configuration builder `Retry`.

## Proposed Changes

### 1. New Public Builder Class: `Maxiviper117\ResultFlow\Retry`
Create a new class to handle the configuration and execution of retries using a fluent interface.

**Location:** `src/Retry.php`

**Class Structure:**
```php
namespace Maxiviper117\ResultFlow;

use Maxiviper117\ResultFlow\Result;
use Throwable;

final class Retry
{
    private int $maxAttempts = 1;
    private int $delayMs = 0;
    private bool $exponential = false;
    private int $jitterMs = 0;
    /** @var callable(mixed, int): bool */
    private $predicate;
    /** @var callable(int, mixed, int): void */
    private $onRetry;

    private function __construct() {
        $this->predicate = fn() => true;
        $this->onRetry = fn() => null;
    }

    public static function config(): self
    {
        return new self();
    }

    public function maxAttempts(int $times): self
    {
        $this->maxAttempts = max(1, $times);
        return $this;
    }

    public function delay(int $ms): self
    {
        $this->delayMs = max(0, $ms);
        return $this;
    }

    public function exponential(bool $enabled = true): self
    {
        $this->exponential = $enabled;
        return $this;
    }

    public function jitter(int $ms): self
    {
        $this->jitterMs = max(0, $ms);
        return $this;
    }

    public function when(callable $predicate): self
    {
        $this->predicate = $predicate;
        return $this;
    }

    public function onRetry(callable $callback): self
    {
        $this->onRetry = $callback;
        return $this;
    }

    public function attempt(callable $fn): Result
    {
        $attempts = 0;
        $lastError = null;
        $lastResult = null;

        while (true) {
            $attempts++;
            
            try {
                $value = $fn();
                
                if ($value instanceof Result) {
                    if ($value->isOk()) {
                        return $value;
                    }
                    $lastError = $value->error();
                    $lastResult = $value;
                } else {
                    return Result::ok($value);
                }
            } catch (Throwable $e) {
                $lastError = $e;
                $lastResult = Result::fail($e);
            }

            if ($attempts >= $this->maxAttempts) {
                return $lastResult;
            }

            if (! ($this->predicate)($lastError, $attempts)) {
                return $lastResult;
            }

            $wait = $this->delayMs;
            if ($this->exponential) {
                $wait = $this->delayMs * (2 ** ($attempts - 1));
            }

            if ($this->jitterMs > 0) {
                $wait += random_int(0, $this->jitterMs);
            }

            ($this->onRetry)($attempts, $lastError, $wait);

            if ($wait > 0) {
                usleep($wait * 1000);
            }
        }
    }
}
```

### 2. Updates to `Result` Class
Add static convenience methods and an entry point to the builder.

**Location:** `src/Result.php`

```php
/**
 * Simple retry with optional delay and exponential backoff.
 * For advanced config (jitter, callbacks), use Result::retrier().
 */
public static function retry(int $times, callable $fn, int $delay = 0, bool $exponential = false): Result
{
    return Retry::config()
        ->maxAttempts($times)
        ->delay($delay)
        ->exponential($exponential)
        ->attempt($fn);
}

/**
 * Access the fluent Retry builder for advanced configuration.
 * 
 * Usage:
 * Result::retrier()
 *     ->maxAttempts(5)
 *     ->jitter(100)
 *     ->attempt(fn() => ...);
 */
public static function retrier(): Retry
{
    return Retry::config();
}
```

### 3. Testing
Create `tests/RetryTest.php` to cover all configurations, success/failure scenarios, and callbacks.

## Comprehensive Usage Examples

### Example 1: Basic Shortcut (Result::retry)
Use the static helper on `Result` for 90% of cases.
```php
$data = Result::retry(3, fn() => Http::get('...'));
```

### Example 2: Advanced Config via Result::retrier()
Use `Result::retrier()` to access the builder when you need Jitter, Logging, or Custom Predicates.
```php
$result = Result::retrier()
    ->maxAttempts(5)
    ->delay(100)
    ->exponential()
    ->jitter(50)
    ->onRetry(function($attempt, $error, $delay) {
        Log::warning("Retry #$attempt in {$delay}ms: " . $error->getMessage());
    })
    ->attempt(fn() => $service->performCriticalAction());
```
