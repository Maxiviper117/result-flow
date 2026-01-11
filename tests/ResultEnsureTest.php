<?php

use Maxiviper117\ResultFlow\Result;

describe('ensure()', function () {
    it('passes when predicate is true', function () {
        $result = Result::ok(10)
            ->ensure(fn ($v) => $v > 5, 'Value must be greater than 5');

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe(10);
    });

    it('fails when predicate is false with static error', function () {
        $result = Result::ok(3)
            ->ensure(fn ($v) => $v > 5, 'Value must be greater than 5');

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBe('Value must be greater than 5');
    });

    it('treats string error as value, not callable', function () {
        $result = Result::ok('value')
            ->ensure(fn () => false, 'strlen');

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBe('strlen');
    });

    it('fails when predicate is false with callable error', function () {
        $result = Result::ok(3)
            ->ensure(
                fn ($v) => $v > 5,
                fn ($v) => "Value {$v} is not greater than 5"
            );

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBe('Value 3 is not greater than 5');
    });

    it('receives meta in predicate and error callable', function () {
        $result = Result::ok(10, ['limit' => 5])
            ->ensure(
                fn ($v, $meta) => $v > $meta['limit'],
                fn ($v, $meta) => "Value {$v} must exceed limit {$meta['limit']}"
            );

        expect($result->isOk())->toBeTrue();
    });

    it('skips predicate on failure', function () {
        $called = false;
        $result = Result::fail('already failed')
            ->ensure(function () use (&$called) {
                $called = true;

                return true;
            }, 'error');

        expect($called)->toBeFalse();
        expect($result->isFail())->toBeTrue();
    });

    it('chains multiple ensure calls', function () {
        $result = Result::ok(10)
            ->ensure(fn ($v) => $v > 0, 'Must be positive')
            ->ensure(fn ($v) => $v < 100, 'Must be less than 100')
            ->ensure(fn ($v) => $v % 2 === 0, 'Must be even');

        expect($result->isOk())->toBeTrue();
    });

    it('short-circuits on first failed ensure', function () {
        $result = Result::ok(-5)
            ->ensure(fn ($v) => $v > 0, 'Must be positive')
            ->ensure(fn ($v) => $v < 100, 'Must be less than 100');

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBe('Must be positive');
    });
});
