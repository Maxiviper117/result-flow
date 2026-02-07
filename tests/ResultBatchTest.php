<?php

use Maxiviper117\ResultFlow\Result;

describe('mapItems()', function () {
    it('maps each item to a keyed Result array', function () {
        $mapped = Result::mapItems(
            ['a' => 2, 'b' => 3],
            fn (int $item, string $key) => Result::ok($item * 10, ['key' => $key]),
        );

        expect($mapped)->toHaveKeys(['a', 'b']);
        expect($mapped['a']->isOk())->toBeTrue();
        expect($mapped['a']->value())->toBe(20);
        expect($mapped['b']->isOk())->toBeTrue();
        expect($mapped['b']->value())->toBe(30);
    });

    it('wraps plain callback values as successes', function () {
        $mapped = Result::mapItems(
            ['x' => 4],
            fn (int $item) => $item + 1,
        );

        expect($mapped['x']->isOk())->toBeTrue();
        expect($mapped['x']->value())->toBe(5);
    });

    it('captures thrown exceptions as failures', function () {
        $mapped = Result::mapItems(
            ['safe' => 1, 'boom' => 2],
            function (int $item): int {
                if ($item === 2) {
                    throw new RuntimeException('explode');
                }

                return $item;
            },
        );

        expect($mapped['safe']->isOk())->toBeTrue();
        expect($mapped['boom']->isFail())->toBeTrue();
        expect($mapped['boom']->error())->toBeInstanceOf(RuntimeException::class);
        expect($mapped['boom']->error()->getMessage())->toBe('explode');
    });
});

describe('mapAll()', function () {
    it('returns keyed success values when all mapped items succeed', function () {
        $result = Result::mapAll(
            ['a' => 2, 'b' => 5],
            fn (int $item) => $item * 2,
        );

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe(['a' => 4, 'b' => 10]);
    });

    it('short-circuits on the first failure', function () {
        $visited = [];

        $result = Result::mapAll(
            ['a' => 1, 'b' => 2, 'c' => 3],
            function (int $item, string $key) use (&$visited) {
                $visited[] = $key;

                if ($item === 2) {
                    return Result::fail('bad-b');
                }

                return Result::ok($item);
            },
        );

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBe('bad-b');
        expect($visited)->toBe(['a', 'b']);
    });

    it('merges metadata from processed results in order', function () {
        $result = Result::mapAll(
            ['a' => 1, 'b' => 2, 'c' => 3],
            function (int $item, string $key) {
                if ($item === 2) {
                    return Result::fail('fail-b', ['step' => 'b', 'b' => true]);
                }

                return Result::ok($item * 10, ['step' => $key, 'a' => true]);
            },
        );

        expect($result->isFail())->toBeTrue();
        expect($result->meta())->toBe([
            'step' => 'b',
            'a' => true,
            'b' => true,
        ]);
    });
});

describe('mapCollectErrors()', function () {
    it('returns keyed success values when every mapped item succeeds', function () {
        $result = Result::mapCollectErrors(
            ['a' => 1, 'b' => 2],
            fn (int $item) => Result::ok($item + 10),
        );

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe(['a' => 11, 'b' => 12]);
    });

    it('collects all errors by key and keeps processing', function () {
        $visited = [];

        $result = Result::mapCollectErrors(
            ['a' => 1, 'b' => 2, 'c' => 3],
            function (int $item, string $key) use (&$visited) {
                $visited[] = $key;

                if ($item % 2 === 1) {
                    return Result::fail("err-{$key}");
                }

                return Result::ok($item * 10);
            },
        );

        expect($visited)->toBe(['a', 'b', 'c']);
        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBe([
            'a' => 'err-a',
            'c' => 'err-c',
        ]);
    });

    it('captures thrown exceptions inside collected errors', function () {
        $result = Result::mapCollectErrors(
            ['x' => 1, 'y' => 2],
            function (int $item): Result {
                if ($item === 2) {
                    throw new RuntimeException('explode-y');
                }

                return Result::ok($item);
            },
        );

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toHaveKey('y');
        expect($result->error()['y'])->toBeInstanceOf(RuntimeException::class);
        expect($result->error()['y']->getMessage())->toBe('explode-y');
    });

    it('does not expose partial successes in the value channel when failing', function () {
        $result = Result::mapCollectErrors(
            ['a' => 1, 'b' => 2],
            fn (int $item) => $item === 1 ? Result::ok($item) : Result::fail('bad-b'),
        );

        expect($result->isFail())->toBeTrue();
        expect($result->value())->toBeNull();
    });

    it('merges metadata from all mapped results in order', function () {
        $result = Result::mapCollectErrors(
            ['a' => 1, 'b' => 2, 'c' => 3],
            function (int $item, string $key) {
                if ($item === 2) {
                    return Result::fail('bad-b', ['step' => 'b', 'b' => true]);
                }

                return Result::ok($item, ['step' => $key, $key => true]);
            },
        );

        expect($result->isFail())->toBeTrue();
        expect($result->meta())->toBe([
            'step' => 'c',
            'a' => true,
            'b' => true,
            'c' => true,
        ]);
    });
});

describe('batch mapping edge cases', function () {
    it('handles empty collections', function () {
        expect(Result::mapItems([], fn (mixed $item) => $item))->toBe([]);

        $all = Result::mapAll([], fn (mixed $item) => $item);
        expect($all->isOk())->toBeTrue();
        expect($all->value())->toBe([]);

        $allErrors = Result::mapCollectErrors([], fn (mixed $item) => $item);
        expect($allErrors->isOk())->toBeTrue();
        expect($allErrors->value())->toBe([]);
    });
});
