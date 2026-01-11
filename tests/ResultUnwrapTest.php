<?php

use Maxiviper117\ResultFlow\Result;

describe('throwIfFail()', function () {
    it('returns $this on success', function () {
        $original = Result::ok('value', ['key' => 'meta']);
        $returned = $original->throwIfFail();

        expect($returned)->toBe($original);
        expect($returned->value())->toBe('value');
        expect($returned->meta())->toBe(['key' => 'meta']);
    });

    it('is chainable on success', function () {
        $result = Result::ok(5)
            ->throwIfFail()
            ->map(fn ($v) => $v * 2);

        expect($result->value())->toBe(10);
    });

    it('throws Throwable error directly', function () {
        $exception = new \InvalidArgumentException('Custom exception');

        $this->expectExceptionObject($exception);

        Result::fail($exception)->throwIfFail();
    });

    it('throws RuntimeException for string error', function () {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Something went wrong');

        Result::fail('Something went wrong')->throwIfFail();
    });

    it('throws RuntimeException with JSON for non-string non-throwable error', function () {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('{"code":500,"message":"Server error"}');

        Result::fail(['code' => 500, 'message' => 'Server error'])->throwIfFail();
    });

    it('throws RuntimeException with fallback for non-encodable error', function () {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('INF');

        Result::fail(INF)->throwIfFail();
    });
});

describe('unwrapOrElse()', function () {
    it('returns value on success', function () {
        $result = Result::ok('success-value')
            ->unwrapOrElse(fn () => 'default');

        expect($result)->toBe('success-value');
    });

    it('computes default from error on failure', function () {
        $result = Result::fail('error-code')
            ->unwrapOrElse(fn ($e) => "Fallback for: {$e}");

        expect($result)->toBe('Fallback for: error-code');
    });

    it('receives meta in callback', function () {
        $result = Result::fail('error', ['fallback' => 'meta-default'])
            ->unwrapOrElse(fn ($e, $meta) => $meta['fallback']);

        expect($result)->toBe('meta-default');
    });

    it('callback is not called on success', function () {
        $called = false;
        Result::ok('value')
            ->unwrapOrElse(function () use (&$called) {
                $called = true;

                return 'default';
            });

        expect($called)->toBeFalse();
    });
});

describe('getOrThrow()', function () {
    it('returns value on success', function () {
        $value = Result::ok('success')
            ->getOrThrow(fn () => new \RuntimeException('Should not throw'));

        expect($value)->toBe('success');
    });

    it('throws custom exception on failure', function () {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom error: validation failed');

        Result::fail('validation failed')
            ->getOrThrow(fn ($e) => new \InvalidArgumentException("Custom error: {$e}"));
    });

    it('receives meta in exception factory', function () {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error code: 404');

        Result::fail('not found', ['code' => 404])
            ->getOrThrow(fn ($e, $meta) => new \RuntimeException("Error code: {$meta['code']}"));
    });
});
