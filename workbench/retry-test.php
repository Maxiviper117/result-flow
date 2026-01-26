<?php

declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

echo "--- ResultFlow Retry Manual Playground ---

";

/**
 * Example 1: Basic Retry
 * Simple usage for common cases.
 */
echo "1. Basic Retry (3 attempts, success on 3rd):
";
$attempts = 0;
$result = Result::retry(3, function() use (&$attempts) {
    $attempts++;
    echo "   [Execution] Attempt #$attempts
";
    if ($attempts < 3) {
        return Result::fail("Temporary failure");
    }
    return Result::ok("Success after $attempts attempts");
});
echo "Final Result: " . ($result->isOk() ? "OK: " . $result->value() : "FAIL: " . $result->error()) . "

";

/**
 * Example 2: Advanced Retry with Builder
 * Demonstrates exponential backoff, jitter, and custom callbacks.
 */
echo "2. Advanced Retry (Exponential, Jitter, Callbacks):
";
$advAttempts = 0;
$advResult = Result::retrier()
    ->maxAttempts(4)
    ->delay(50) // 50ms base
    ->exponential()
    ->jitter(20)
    ->onRetry(function(int $attempt, $error, int $wait) {
        $errorMessage = $error instanceof \Throwable ? $error->getMessage() : (string)$error;
        echo "   [Callback] Attempt $attempt failed ($errorMessage). Waiting {$wait}ms before next try...
";
    })
    ->attempt(function() use (&$advAttempts) {
        $advAttempts++;
        echo "   [Execution] Running attempt #$advAttempts...";
        if ($advAttempts < 4) {
            throw new \RuntimeException("Service Unavailable");
        }
        return "Builder recovered successfully!";
    });
echo "Final Result: " . $advResult->value() . "

";

/**
 * Example 3: Conditional Retries
 * Using `when()` to stop retrying based on the error or attempt count.
 */
echo "3. Conditional Retry (Stop if specific error occurs):
";
$condAttempts = 0;
$condResult = Result::retrier()
    ->maxAttempts(5)
    ->when(function($error, $attempt) {
        if ($error === 'FATAL') {
            echo "   [Predicate] Fatal error detected. Stopping.
";
            return false;
        }
        return true;
    })
    ->attempt(function() use (&$condAttempts) {
        $condAttempts++;
        echo "   [Execution] Attempt #$condAttempts...
";
        if ($condAttempts === 2) {
            return Result::fail('FATAL');
        }
        return Result::fail('Transient');
    });
echo "Final Result: " . $condResult->error() . " (Attempts: $condAttempts)
";
