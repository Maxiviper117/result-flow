<?php

use Maxiviper117\ResultFlow\Result;

describe('inspect() and inspectError()', function () {
    it('inspect() taps success value', function () {
        $inspected = null;
        Result::ok('hello')
            ->inspect(function ($v) use (&$inspected) {
                $inspected = $v;
            });

        expect($inspected)->toBe('hello');
    });

    it('inspect() does not run on failure', function () {
        $called = false;
        Result::fail('error')
            ->inspect(function () use (&$called) {
                $called = true;
            });

        expect($called)->toBeFalse();
    });

    it('inspectError() taps failure value', function () {
        $inspected = null;
        Result::fail('error-message')
            ->inspectError(function ($e) use (&$inspected) {
                $inspected = $e;
            });

        expect($inspected)->toBe('error-message');
    });

    it('inspectError() does not run on success', function () {
        $called = false;
        Result::ok('value')
            ->inspectError(function () use (&$called) {
                $called = true;
            });

        expect($called)->toBeFalse();
    });
});
