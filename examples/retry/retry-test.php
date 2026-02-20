<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

function printHeader(string $title): void
{
    echo "\n=== {$title} ===\n";
}

function describeResult(Result $result): array
{
    $error = $result->error();

    return [
        'ok' => $result->isOk(),
        'value' => $result->value(),
        'error' => $error instanceof Throwable ? $error->getMessage() : $error,
        'meta' => $result->meta(),
    ];
}

function printResult(string $label, Result $result): void
{
    echo $label.":\n";
    echo json_encode(describeResult($result), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
}

echo "--- Result::retry() + Result::retrier() Example ---\n";

/**
 * Example 1: Basic Retry
 * Simple usage for common cases.
 */
printHeader('1) Basic retry: throw twice, succeed on third');
$attempts = 0;
$basic = Result::retry(3, function () use (&$attempts) {
    $attempts++;
    echo "Attempt {$attempts}\n";

    if ($attempts < 3) {
        throw new RuntimeException('Temporary network issue');
    }

    return 'ok-after-retries';
});
printResult('Final', $basic);

/**
 * Example 2: Advanced Retry with Builder
 * Demonstrates exponential backoff, jitter, and custom callbacks.
 */
printHeader('2) Advanced retrier: delay/backoff/jitter + retry callback');
$advAttempts = 0;
$retryLog = [];
$advResult = Result::retrier()
    ->maxAttempts(4)
    ->delay(25)
    ->exponential()
    ->jitter(10)
    ->attachAttemptMeta()
    ->onRetry(function (int $attempt, mixed $error, int $wait) use (&$retryLog): void {
        $errorMessage = $error instanceof Throwable ? $error->getMessage() : (string) $error;
        $retryLog[] = "attempt={$attempt}, error={$errorMessage}, wait_ms={$wait}";
    })
    ->attempt(function () use (&$advAttempts) {
        $advAttempts++;
        echo "Attempt {$advAttempts}\n";

        if ($advAttempts < 4) {
            throw new RuntimeException('Service unavailable');
        }

        return ['status' => 'recovered', 'attempts' => $advAttempts];
    });
printResult('Final', $advResult);
echo "onRetry log:\n";
echo json_encode($retryLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

/**
 * Example 3: Conditional Retries
 * Using `when()` to stop retrying based on the error or attempt count.
 */
printHeader('3) Conditional retry: stop on fatal error');
$condAttempts = 0;
$condResult = Result::retrier()
    ->maxAttempts(5)
    ->when(function (mixed $error, int $attempt): bool {
        if ($error === 'FATAL') {
            echo "Predicate stopped retries on attempt {$attempt}\n";

            return false;
        }

        return true;
    })
    ->attempt(function () use (&$condAttempts) {
        $condAttempts++;
        echo "Attempt {$condAttempts}\n";

        if ($condAttempts === 2) {
            return Result::fail('FATAL');
        }

        return Result::fail('Transient');
    });
printResult('Final', $condResult);
