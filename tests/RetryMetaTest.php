<?php

use Maxiviper117\ResultFlow\Result;

it('does not add retry metadata by default', function () {
    $result = Result::retrier()
        ->maxAttempts(3)
        ->attempt(fn () => 'plain');

    expect(array_key_exists('retry', $result->meta()))->toBeFalse();
});

it('attaches attempt metadata for plain-success when enabled', function () {
    $result = Result::retrier()
        ->maxAttempts(3)
        ->attachAttemptMeta()
        ->attempt(fn () => 'value');

    expect($result->isOk())->toBeTrue();
    expect($result->value())->toBe('value');
    expect($result->meta())->toHaveKey('retry');
    expect($result->meta()['retry']['attempts'])->toBe(1);
});

it('merges attempt metadata with successful Result returns', function () {
    $attempts = 0;

    $result = Result::retrier()
        ->maxAttempts(3)
        ->attachAttemptMeta()
        ->attempt(function () use (&$attempts) {
            $attempts++;

            if ($attempts < 2) {
                return Result::fail('oops', ['stage' => 'first']);
            }

            return Result::ok('done', ['stage' => 'second']);
        });

    expect($result->isOk())->toBeTrue();
    expect($result->value())->toBe('done');
    expect($result->meta())->toHaveKey('stage');
    expect($result->meta())->toHaveKey('retry');
    expect($result->meta()['retry']['attempts'])->toBe(2);
    expect($attempts)->toBe(2);
});

it('attaches attempt metadata when retries exhaust and fail', function () {
    $result = Result::retrier()
        ->maxAttempts(3)
        ->attachAttemptMeta()
        ->attempt(function () use (&$attempts) {
            $attempts++;

            throw new Exception('fail');
        });

    expect($result->isFail())->toBeTrue();
    expect($result->meta())->toHaveKey('retry');
    expect($result->meta()['retry']['attempts'])->toBe(3);
    expect($attempts)->toBe(3);
});
