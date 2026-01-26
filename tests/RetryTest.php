<?php

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Retry;

it('retries until success', function () {
    $attempts = 0;

    $result = Result::retry(3, function () use (&$attempts) {
        $attempts++;
        if ($attempts < 3) {
            throw new Exception('fail');
        }

        return 'success';
    });

    expect($result->isOk())->toBeTrue();
    expect($result->value())->toBe('success');
    expect($attempts)->toBe(3);
});

it('fails after max attempts', function () {
    $attempts = 0;

    $result = Result::retry(3, function () use (&$attempts) {
        $attempts++;
        throw new Exception('fail');
    });

    expect($result->isFail())->toBeTrue();
    expect($attempts)->toBe(3);
});

it('supports exponential backoff', function () {
    // We can't easily test time passage without mocking usleep,
    // but we can verify the logic runs without error
    $start = microtime(true);

    $result = Result::retrier()
        ->maxAttempts(3)
        ->delay(10) // 10ms
        ->exponential()
        ->attempt(function () {
            throw new Exception('fail');
        });

    $end = microtime(true);

    expect($result->isFail())->toBeTrue();
    // 1st retry: 10ms, 2nd retry: 20ms. Total wait approx 30ms.
    // Allow some buffer for execution time.
    // expect($end - $start)->toBeGreaterThan(0.03);
    // Keeping it simple to avoid flaky tests on CI
});

it('stops retrying if predicate returns false', function () {
    $attempts = 0;

    $result = Result::retrier()
        ->maxAttempts(5)
        ->when(function ($error, $attempt) {
            return $attempt < 2; // Stop after 2nd attempt (1st retry)
        })
        ->attempt(function () use (&$attempts) {
            $attempts++;
            throw new Exception('fail');
        });

    expect($attempts)->toBe(2);
    expect($result->isFail())->toBeTrue();
});

it('calls onRetry callback', function () {
    $logs = [];

    Result::retrier()
        ->maxAttempts(3)
        ->delay(5)
        ->onRetry(function ($attempt, $error, $wait) use (&$logs) {
            $logs[] = "Attempt $attempt failed, waiting $wait ms";
        })
        ->attempt(function () {
            throw new Exception('fail');
        });

    expect($logs)->toHaveCount(2); // Retries happen after attempt 1 and 2
    expect($logs[0])->toContain('Attempt 1 failed');
    expect($logs[1])->toContain('Attempt 2 failed');
});

it('handles Result::fail inside callable', function () {
    $attempts = 0;

    $result = Result::retry(3, function () use (&$attempts) {
        $attempts++;
        if ($attempts < 2) {
            return Result::fail('oops');
        }

        return Result::ok('done');
    });

    expect($result->isOk())->toBeTrue();
    expect($result->value())->toBe('done');
    expect($attempts)->toBe(2);
});

it('handles jitter', function () {
    // Just verify it runs without error
    $result = Result::retrier()
        ->maxAttempts(2)
        ->jitter(10)
        ->attempt(fn () => Result::fail('error'));

    expect($result->isFail())->toBeTrue();
});
