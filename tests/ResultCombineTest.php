<?php

use Maxiviper117\ResultFlow\Result;

describe('combine()', function () {
    it('combines multiple success results into array of values', function () {
        $results = [
            Result::ok('a'),
            Result::ok('b'),
            Result::ok('c'),
        ];

        $combined = Result::combine($results);

        expect($combined->isOk())->toBeTrue();
        expect($combined->value())->toBe(['a', 'b', 'c']);
    });

    it('short-circuits on first failure', function () {
        $results = [
            Result::ok('a'),
            Result::fail('error1'),
            Result::fail('error2'),
        ];

        $combined = Result::combine($results);

        expect($combined->isFail())->toBeTrue();
        expect($combined->error())->toBe('error1');
    });

    it('merges metadata from all results', function () {
        $results = [
            Result::ok('a', ['key1' => 'val1']),
            Result::ok('b', ['key2' => 'val2']),
        ];

        $combined = Result::combine($results);

        expect($combined->meta())->toBe(['key1' => 'val1', 'key2' => 'val2']);
    });

    it('handles empty array', function () {
        $combined = Result::combine([]);

        expect($combined->isOk())->toBeTrue();
        expect($combined->value())->toBe([]);
    });
});

describe('combineAll()', function () {
    it('collects all errors when multiple results fail', function () {
        $results = [
            Result::fail('error1'),
            Result::ok('value'),
            Result::fail('error2'),
        ];

        $combined = Result::combineAll($results);

        expect($combined->isFail())->toBeTrue();
        expect($combined->error())->toBe(['error1', 'error2']);
    });

    it('returns success with all values when no failures', function () {
        $results = [
            Result::ok('a'),
            Result::ok('b'),
            Result::ok('c'),
        ];

        $combined = Result::combineAll($results);

        expect($combined->isOk())->toBeTrue();
        expect($combined->value())->toBe(['a', 'b', 'c']);
    });

    it('collects single error in array', function () {
        $results = [
            Result::ok('a'),
            Result::fail('only-error'),
            Result::ok('b'),
        ];

        $combined = Result::combineAll($results);

        expect($combined->isFail())->toBeTrue();
        expect($combined->error())->toBe(['only-error']);
    });
});
