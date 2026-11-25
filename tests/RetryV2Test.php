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
