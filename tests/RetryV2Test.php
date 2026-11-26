<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Retry;

it('retries only failures selected by the predicate and keeps final metadata', function () {
    $attempts = 0;
    $events = [];
    $result = Retry::config()->maxAttempts(3)->attachAttemptMeta()
        ->when(fn ($error) => $error === 'transient')
        ->onRetry(function ($attempt, $error, $delay) use (&$events) {
            $events[] = [$attempt, $error, $delay];
        })->attempt(function () use (&$attempts) {
            $attempts++;

            return $attempts === 1 ? Result::fail('transient') : Result::ok('done', ['trace' => 'a']);
        });
    expect($attempts)->toBe(2)->and($result->unwrap())->toBe('done')
        ->and($result->meta())->toBe(['trace' => 'a', 'retry' => ['attempts' => 2]])
        ->and($events)->toBe([[1, 'transient', 0]]);
});

it('stops on a non-transient error and retains its identity', function () {
    $attempts = 0;
    $error = new RuntimeException('permanent');
    $result = Retry::config()->maxAttempts(3)->when(fn () => false)->attempt(function () use (&$attempts, $error) {
        $attempts++;
        throw $error;
    });
    expect($attempts)->toBe(1)->and($result->match(fn ($v) => $v, fn ($e) => $e))->toBe($error);
});

it('propagates exceptions thrown by retry predicates and hooks', function () {
    $predicateError = new LogicException('predicate failed');
    $hookError = new LogicException('hook failed');
    foreach ([
        [Retry::config()->maxAttempts(2)->when(fn () => throw $predicateError), $predicateError],
        [Retry::config()->maxAttempts(2)->onRetry(fn () => throw $hookError), $hookError],
    ] as [$retry, $expected]) {
        try {
            $retry->attempt(fn () => Result::fail('transient'));
            test()->fail('Expected retry policy exception');
        } catch (LogicException $caught) {
            expect($caught)->toBe($expected);
        }
    }
});

it('keeps zero exponential delays integer across many retries', function () {
    $events = [];
    $result = Retry::config()->maxAttempts(70)->exponential()
        ->onRetry(function (int $attempt, mixed $error, int $delay) use (&$events): void {
            $events[] = $delay;
        })->attempt(fn () => Result::fail('temporary'));
    expect($result->isFail())->toBeTrue()->and($events)->toHaveCount(69)->and(array_unique($events))->toBe([0]);
});

it('rejects delays beyond the portable millisecond limit', function () {
    $calculate = new ReflectionMethod(Retry::class, 'retryDelay');
    $limit = 2_147_483_647;
    expect($calculate->invoke(Retry::config()->delay(1)->exponential(), 3))->toBe(4)
        ->and($calculate->invoke(Retry::config()->delay($limit), 1))->toBe($limit)
        ->and(fn () => $calculate->invoke(Retry::config()->delay(1)->exponential(), 64))->toThrow(OverflowException::class)
        ->and(fn () => $calculate->invoke(Retry::config()->delay($limit)->jitter(1), 1))->toThrow(OverflowException::class)
        ->and(fn () => Retry::config()->maxAttempts(2)->delay($limit)->jitter(1)->onRetry(fn () => throw new LogicException('hook must not run'))->attempt(fn () => Result::fail('temporary')))->toThrow(OverflowException::class);
});

it('splits long waits into seconds and subsecond microseconds', function () {
    $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/Support/RetrySleepProbe.php');
    exec($command, $output, $status);
    expect($status)->toBe(0)->and($output)->toBe(['seconds:1', 'microseconds:500000']);
});
