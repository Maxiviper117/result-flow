<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\UnwrapFailure;

it('chains results and merges metadata with child precedence', function () {
    $result = Result::ok(null, ['trace' => 'a', 'replace' => 1])
        ->andThen(fn ($value) => Result::fail($value, ['replace' => null]));

    expect($result->isFail())->toBeTrue()
        ->and($result->meta())->toBe(['trace' => 'a', 'replace' => null])
        ->and($result->withMeta([])->meta())->toBe([]);
});

it('recovers lazily and merges metadata', function () {
    $called = 0;
    $success = Result::ok('ok')->orElse(function () use (&$called) {
        $called++;

        return Result::ok('unexpected');
    });
    $recovered = Result::fail(null, ['trace' => 1])->orElse(fn ($error) => Result::ok($error, ['child' => 2]));
    expect($called)->toBe(0)->and($success->unwrap())->toBe('ok')
        ->and($recovered->unwrap())->toBeNull()->and($recovered->meta())->toBe(['trace' => 1, 'child' => 2]);
});

it('captures only the selected exception and does not flatten', function () {
    $exception = new RuntimeException('expected');
    expect(Result::attempt(fn () => throw $exception, catch: RuntimeException::class)->error())->toBe($exception)
        ->and(Result::attempt(fn () => Result::ok(1), catch: RuntimeException::class)->unwrap())->toBeInstanceOf(Result::class);
    try {
        Result::attempt(fn () => throw $exception, catch: LogicException::class);
        test()->fail('Expected exception to escape');
    } catch (RuntimeException $caught) {
        expect($caught)->toBe($exception);
    }
});

it('retains the exact failure payload when unwrapping', function () {
    $failure = (object) ['reason' => 'invalid'];
    try {
        Result::fail($failure)->unwrap();
        test()->fail('Expected unwrap to throw');
    } catch (UnwrapFailure $exception) {
        expect($exception->failure)->toBe($failure);
    }
});

it('passes exactly one argument to transformations', function () {
    expect(Result::ok(1, ['trace' => 1])->map(fn (...$args) => $args)->unwrap())->toBe([1]);
    expect(Result::fail(null)->mapError(fn (...$args) => $args)->error())->toBe([null]);
});
