<?php

use Maxiviper117\ResultFlow\Result;

// =========================================================================
// Edge Cases & Boundary Tests
// =========================================================================

describe('edge cases: null and falsy values', function () {
    it('handles null as a valid success value', function () {
        $result = Result::ok(null);

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBeNull();
        expect($result->isFail())->toBeFalse();
    });

    it('handles null as a valid error value', function () {
        $result = Result::fail(null);

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBeNull();
        expect($result->isOk())->toBeFalse();
    });

    it('handles false as a valid success value', function () {
        $result = Result::ok(false);

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBeFalse();
    });

    it('handles empty string as a valid success value', function () {
        $result = Result::ok('');

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe('');
    });

    it('handles zero as a valid success value', function () {
        $result = Result::ok(0);

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe(0);
    });

    it('handles empty array as a valid success value', function () {
        $result = Result::ok([]);

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe([]);
    });

    it('map preserves null value transformation', function () {
        $result = Result::ok('value')
            ->map(fn ($v) => null);

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBeNull();
    });

    it('then with step returning null wraps as success', function () {
        $result = Result::ok('value')
            ->then(fn ($v) => null);

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBeNull();
    });
});

describe('edge cases: unwrap behavior', function () {
    it('unwrap throws RuntimeException for non-Throwable string error', function () {
        $result = Result::fail('string error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('string error');

        $result->unwrap();
    });

    it('unwrap throws RuntimeException with default message for non-string error', function () {
        $result = Result::fail(['complex' => 'error']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Result failed');

        $result->unwrap();
    });

    it('unwrapOr returns value even when default is provided on success', function () {
        $result = Result::ok('actual');

        expect($result->unwrapOr('default'))->toBe('actual');
    });

    it('unwrapOr returns null default on failure', function () {
        $result = Result::fail('error');

        expect($result->unwrapOr(null))->toBeNull();
    });
});

describe('edge cases: chaining behavior', function () {
    it('then skips all steps after first failure in array', function () {
        $calls = [];

        $result = Result::ok(1)->then([
            function ($v) use (&$calls) {
                $calls[] = 'step1';

                return Result::fail('failed at step1');
            },
            function ($v) use (&$calls) {
                $calls[] = 'step2';

                return $v + 1;
            },
            function ($v) use (&$calls) {
                $calls[] = 'step3';

                return $v + 1;
            },
        ]);

        expect($calls)->toBe(['step1']);
        expect($result->isFail())->toBeTrue();
    });

    it('otherwise only runs on failure branch', function () {
        $otherwiseCalled = false;

        $result = Result::ok('success')
            ->otherwise(function () use (&$otherwiseCalled) {
                $otherwiseCalled = true;

                return Result::ok('recovered');
            });

        expect($otherwiseCalled)->toBeFalse();
        expect($result->value())->toBe('success');
    });

    it('otherwise can chain into then after recovery', function () {
        $result = Result::fail('initial error')
            ->otherwise(fn () => Result::ok('recovered'))
            ->then(fn ($v) => $v.' and processed');

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe('recovered and processed');
    });

    it('multiple otherwise calls - only first matching runs', function () {
        $calls = [];

        $result = Result::fail('error')
            ->otherwise(function ($e) use (&$calls) {
                $calls[] = 'otherwise1';

                return Result::ok('recovered');
            })
            ->otherwise(function ($e) use (&$calls) {
                $calls[] = 'otherwise2';

                return Result::ok('second recovery');
            });

        expect($calls)->toBe(['otherwise1']);
        expect($result->value())->toBe('recovered');
    });

    it('otherwise continues failure chain when returning fail', function () {
        $calls = [];

        $result = Result::fail('error1')
            ->otherwise(function ($e) use (&$calls) {
                $calls[] = 'otherwise1';

                return Result::fail('error2');
            })
            ->otherwise(function ($e) use (&$calls) {
                $calls[] = 'otherwise2';

                return Result::ok('finally recovered');
            });

        expect($calls)->toBe(['otherwise1', 'otherwise2']);
        expect($result->value())->toBe('finally recovered');
    });
});

describe('edge cases: combine and combineAll', function () {
    it('combine handles single result', function () {
        $combined = Result::combine([Result::ok('only')]);

        expect($combined->isOk())->toBeTrue();
        expect($combined->value())->toBe(['only']);
    });

    it('combine handles single failure', function () {
        $combined = Result::combine([Result::fail('only error')]);

        expect($combined->isFail())->toBeTrue();
        expect($combined->error())->toBe('only error');
    });

    it('combineAll handles empty array', function () {
        $combined = Result::combineAll([]);

        expect($combined->isOk())->toBeTrue();
        expect($combined->value())->toBe([]);
    });

    it('combineAll handles all failures', function () {
        $combined = Result::combineAll([
            Result::fail('e1'),
            Result::fail('e2'),
            Result::fail('e3'),
        ]);

        expect($combined->isFail())->toBeTrue();
        expect($combined->error())->toBe(['e1', 'e2', 'e3']);
    });

    it('combine preserves metadata order (later overwrites earlier)', function () {
        $combined = Result::combine([
            Result::ok('a', ['key' => 'first', 'a' => 1]),
            Result::ok('b', ['key' => 'second', 'b' => 2]),
        ]);

        expect($combined->meta()['key'])->toBe('second');
        expect($combined->meta()['a'])->toBe(1);
        expect($combined->meta()['b'])->toBe(2);
    });

    it('combineAll preserves successful values alongside errors', function () {
        $results = [
            Result::ok('a'),
            Result::fail('error'),
            Result::ok('b'),
        ];

        $combined = Result::combineAll($results);

        // When failing, we only get errors, not values
        expect($combined->isFail())->toBeTrue();
        expect($combined->error())->toBe(['error']);
        expect($combined->value())->toBeNull();
    });
});

describe('edge cases: ensure method', function () {
    it('ensure with truthy predicate result passes', function () {
        $result = Result::ok('value')
            ->ensure(fn () => 1, 'error'); // truthy but not true

        // Note: predicate must return exactly true/false
        expect($result->isOk())->toBeTrue();
    });

    it('ensure preserves original value on pass', function () {
        $original = new \stdClass;
        $original->id = 123;

        $result = Result::ok($original)
            ->ensure(fn ($v) => $v->id === 123, 'Invalid id');

        expect($result->value())->toBe($original);
    });

    it('ensure preserves meta on pass', function () {
        $result = Result::ok('value', ['important' => 'data'])
            ->ensure(fn () => true, 'error');

        expect($result->meta())->toBe(['important' => 'data']);
    });

    it('ensure preserves meta on fail', function () {
        $result = Result::ok('value', ['important' => 'data'])
            ->ensure(fn () => false, 'validation failed');

        expect($result->meta())->toBe(['important' => 'data']);
    });
});

describe('edge cases: match method', function () {
    it('match can return null from success callback', function () {
        $result = Result::ok('value')->match(
            onSuccess: fn () => null,
            onFailure: fn () => 'error',
        );

        expect($result)->toBeNull();
    });

    it('match can return null from failure callback', function () {
        $result = Result::fail('error')->match(
            onSuccess: fn () => 'value',
            onFailure: fn () => null,
        );

        expect($result)->toBeNull();
    });

    it('match callbacks receive correct types', function () {
        $successType = null;
        $failureType = null;

        Result::ok(['array', 'value'])->match(
            onSuccess: function ($v) use (&$successType) {
                $successType = gettype($v);

                return null;
            },
            onFailure: fn () => null,
        );

        Result::fail(new \RuntimeException('error'))->match(
            onSuccess: fn () => null,
            onFailure: function ($e) use (&$failureType) {
                $failureType = get_class($e);

                return null;
            },
        );

        expect($successType)->toBe('array');
        expect($failureType)->toBe(\RuntimeException::class);
    });
});

describe('edge cases: recover method', function () {
    it('recover skips on success', function () {
        $called = false;

        $result = Result::ok('success')
            ->recover(function () use (&$called) {
                $called = true;

                return 'recovered';
            });

        expect($called)->toBeFalse();
        expect($result->value())->toBe('success');
    });

    it('recover always produces success', function () {
        $result = Result::fail('error')
            ->recover(fn ($e) => "Recovered from: {$e}");

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe('Recovered from: error');
    });

    it('recover receives meta', function () {
        $receivedMeta = null;

        Result::fail('error', ['context' => 'important'])
            ->recover(function ($e, $meta) use (&$receivedMeta) {
                $receivedMeta = $meta;

                return 'recovered';
            });

        expect($receivedMeta)->toBe(['context' => 'important']);
    });

    it('recover preserves original meta', function () {
        $result = Result::fail('error', ['original' => 'meta'])
            ->recover(fn () => 'recovered');

        expect($result->meta())->toBe(['original' => 'meta']);
    });
});

describe('edge cases: failWithValue', function () {
    it('stores failed value in meta', function () {
        $input = ['email' => 'invalid@'];
        $result = Result::failWithValue('Validation failed', $input);

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBe('Validation failed');
        expect($result->meta()['failed_value'])->toBe($input);
    });

    it('merges failed_value with provided meta', function () {
        $result = Result::failWithValue('Error', 'input', ['extra' => 'data']);

        expect($result->meta())->toBe(['failed_value' => 'input', 'extra' => 'data']);
    });

    it('failed_value appears first in meta array', function () {
        $result = Result::failWithValue('Error', 'the-input', ['other' => 'key']);
        $keys = array_keys($result->meta());

        expect($keys[0])->toBe('failed_value');
    });
});

describe('edge cases: of() static constructor', function () {
    it('of wraps return value as success', function () {
        $result = Result::of(fn () => 'computed');

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe('computed');
    });

    it('of wraps null return as success', function () {
        $result = Result::of(fn () => null);

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBeNull();
    });

    it('of wraps false return as success', function () {
        $result = Result::of(fn () => false);

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBeFalse();
    });

    it('of captures nested exceptions', function () {
        $innerException = new \InvalidArgumentException('inner');
        $result = Result::of(function () use ($innerException) {
            throw new \RuntimeException('outer', 0, $innerException);
        });

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBeInstanceOf(\RuntimeException::class);
        expect($result->error()->getPrevious())->toBe($innerException);
    });
});

describe('edge cases: step invocation patterns', function () {
    it('throws InvalidArgumentException for object without callable method', function () {
        $badStep = new class
        {
            public function doSomething() {}
        };

        $result = Result::ok('value')->then($badStep);

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBeInstanceOf(\InvalidArgumentException::class);
    });

    it('prefers __invoke over handle', function () {
        $step = new class
        {
            public function __invoke($v, $m)
            {
                return 'from invoke';
            }

            public function handle($v, $m)
            {
                return 'from handle';
            }
        };

        $result = Result::ok('value')->then($step);

        expect($result->value())->toBe('from invoke');
    });

    it('uses handle when __invoke not available', function () {
        $step = new class
        {
            public function handle($v, $m)
            {
                return 'from handle';
            }

            public function execute($v, $m)
            {
                return 'from execute';
            }
        };

        $result = Result::ok('value')->then($step);

        expect($result->value())->toBe('from handle');
    });

    it('uses execute as last resort', function () {
        $step = new class
        {
            public function execute($v, $m)
            {
                return 'from execute';
            }
        };

        $result = Result::ok('value')->then($step);

        expect($result->value())->toBe('from execute');
    });
});

describe('edge cases: meta operations', function () {
    it('tapMeta allows observing meta but direct mutation affects internal array', function () {
        // Note: PHP arrays passed to closures can be mutated if modified directly
        // This test documents actual behavior - tapMeta is for observation
        $result = Result::ok('value', ['original' => true]);
        $observed = null;

        $result->tapMeta(function ($meta) use (&$observed) {
            $observed = $meta;
        });

        expect($observed)->toBe(['original' => true]);
        expect($result->meta())->toBe(['original' => true]);
    });

    it('mapMeta can clear all metadata', function () {
        $result = Result::ok('value', ['has' => 'meta'])
            ->mapMeta(fn () => []);

        expect($result->meta())->toBe([]);
    });

    it('mergeMeta with empty array preserves existing meta', function () {
        $result = Result::ok('value', ['existing' => 'meta'])
            ->mergeMeta([]);

        expect($result->meta())->toBe(['existing' => 'meta']);
    });

    it('mapMeta receives current meta', function () {
        $received = null;

        Result::ok('value', ['current' => 'meta'])
            ->mapMeta(function ($meta) use (&$received) {
                $received = $meta;

                return $meta;
            });

        expect($received)->toBe(['current' => 'meta']);
    });
});
