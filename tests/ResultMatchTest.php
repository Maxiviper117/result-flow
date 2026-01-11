<?php

use Maxiviper117\ResultFlow\Result;

describe('match()', function () {
    it('calls onSuccess callback for success result', function () {
        $result = Result::ok('hello')->match(
            onSuccess: fn ($v) => strtoupper($v),
            onFailure: fn ($e) => 'ERROR',
        );

        expect($result)->toBe('HELLO');
    });

    it('calls onFailure callback for failure result', function () {
        $result = Result::fail('oops')->match(
            onSuccess: fn ($v) => 'SUCCESS',
            onFailure: fn ($e) => "Error: {$e}",
        );

        expect($result)->toBe('Error: oops');
    });

    it('passes meta to success callback', function () {
        $result = Result::ok('value', ['id' => 123])->match(
            onSuccess: fn ($v, $meta) => $meta['id'],
            onFailure: fn ($e) => 0,
        );

        expect($result)->toBe(123);
    });

    it('passes meta to failure callback', function () {
        $result = Result::fail('error', ['code' => 500])->match(
            onSuccess: fn ($v) => 0,
            onFailure: fn ($e, $meta) => $meta['code'],
        );

        expect($result)->toBe(500);
    });

    it('can return different types', function () {
        $resultArray = Result::ok(['a', 'b'])->match(
            onSuccess: fn ($v) => count($v),
            onFailure: fn ($e) => -1,
        );

        expect($resultArray)->toBe(2);
    });
});

describe('exception matchers', function () {
    it('catchException recovers from matching Throwable and wraps plain values', function () {
        $result = Result::of(function () {
            throw new \RuntimeException('boom');
        })->catchException([
            \RuntimeException::class => fn (\RuntimeException $e, array $meta) => 'recovered',
        ]);

        expect($result->isOk())->toBeTrue();
        expect($result->unwrap())->toBe('recovered');
    });

    it('catchException returns original result if no handler matches and no fallback provided', function () {
        $ex = new \InvalidArgumentException('bad');
        $result = Result::fail($ex)->catchException([
            \RuntimeException::class => fn (\RuntimeException $e) => Result::ok('recovered'),
        ]);

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBe($ex);
    });

    it('catchException uses fallback when provided', function () {
        $ex = new \InvalidArgumentException('bad');
        $result = Result::fail($ex)->catchException([
            \RuntimeException::class => fn (\RuntimeException $e) => Result::ok('recovered'),
        ], fallback: fn ($err, $meta) => 'fallback');

        expect($result->isOk())->toBeTrue();
        expect($result->unwrap())->toBe('fallback');
    });

    it('matchException handles success values and exception class matches, otherwise falls back', function () {
        $ok = Result::ok('hello')->matchException([], onSuccess: fn ($v) => strtoupper($v), onUnhandled: fn () => 'nope');
        expect($ok)->toBe('HELLO');

        $handled = Result::fail(new \RuntimeException('boom'))->matchException([
            \RuntimeException::class => fn (\RuntimeException $e) => 'handled',
        ], onSuccess: fn () => 'ignored', onUnhandled: fn () => 'fallback');
        expect($handled)->toBe('handled');

        $unhandled = Result::fail('plain-error')->matchException([
            \RuntimeException::class => fn (\RuntimeException $e) => 'handled',
        ], onSuccess: fn () => 'ignored', onUnhandled: fn ($e, $meta) => 'unhandled');
        expect($unhandled)->toBe('unhandled');
    });
});
