<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\ResultBatch;

it('combines evaluated results and preserves keys and metadata', function () {
    $results = [
        'first' => Result::ok(1, ['trace' => 'a', 'nested' => ['old' => true]]),
        'second' => Result::ok(2, ['nested' => null]),
    ];
    $combined = ResultBatch::firstFailure($results);
    expect($combined->unwrap())->toBe(['first' => 1, 'second' => 2])
        ->and($combined->meta())->toBe(['trace' => 'a', 'nested' => null]);
});

it('collects all failures, including null, without returning partial successes', function () {
    $result = ResultBatch::allErrors([
        'valid' => Result::ok(1),
        'missing' => Result::fail(null, ['a' => 1]),
        'invalid' => Result::fail('bad', ['a' => 2]),
    ]);
    expect($result->isFail())->toBeTrue()
        ->and($result->match(fn ($v) => $v, fn ($e) => $e))->toBe(['missing' => null, 'invalid' => 'bad'])
        ->and($result->meta())->toBe(['a' => 2]);
});

it('stops evaluating mapped items at the first failure', function () {
    $seen = [];
    $result = ResultBatch::mapFirstFailure(['a' => 1, 'b' => 2, 'c' => 3], function ($value, $key) use (&$seen) {
        $seen[] = $key;

        return $value === 2 ? Result::fail('bad') : Result::ok($value);
    });
    expect($seen)->toBe(['a', 'b'])->and($result->isFail())->toBeTrue();
});

it('evaluates all items when collecting errors and propagates exceptions', function () {
    $seen = [];
    $result = ResultBatch::mapAllErrors([1, 2, 3], function ($value) use (&$seen) {
        $seen[] = $value;

        return $value !== 2 ? Result::fail($value) : Result::ok($value);
    });
    expect($seen)->toBe([1, 2, 3])->and($result->match(fn ($v) => $v, fn ($e) => $e))->toBe([0 => 1, 2 => 3]);
    $exception = new RuntimeException('unexpected');
    try {
        ResultBatch::mapAllErrors([1], fn () => throw $exception);
        test()->fail('Expected exception');
    } catch (RuntimeException $caught) {
        expect($caught)->toBe($exception);
    }
});

it('merges metadata only through the first visited failure', function () {
    $result = ResultBatch::firstFailure([
        'first' => Result::ok(1, ['first' => true, 'collision' => 'old']),
        'failed' => Result::fail('bad', ['collision' => null]),
        'later' => Result::ok(3, ['later' => true]),
    ]);
    expect($result->isFail())->toBeTrue()
        ->and($result->meta())->toBe(['first' => true, 'collision' => null]);
});

it('returns an empty success for empty inputs in both batch strategies', function () {
    expect(ResultBatch::firstFailure([])->unwrap())->toBe([])
        ->and(ResultBatch::allErrors([])->unwrap())->toBe([])
        ->and(ResultBatch::mapFirstFailure([], fn () => throw new LogicException)->unwrap())->toBe([])
        ->and(ResultBatch::mapAllErrors([], fn () => throw new LogicException)->unwrap())->toBe([]);
});
