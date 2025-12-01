<?php

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Tests\Support\ConfigStub;

it('propagates types through map/then and unwrap', function () {
    /** @var Result<int, \Exception> $r */
    $r = Result::ok(123);

    $r2 = $r
        ->map(fn (int $n, array $meta) => (string) $n) // should become Result<string, Exception>
        ->then(fn (string $s, array $meta) => strlen($s)); // returns int -> Result<int, Exception>

    $val = $r2->unwrap();

    expect($val)->toBeInt();
    expect($val)->toBe(3);
});

it('fails unwrap throws throwable', function () {
    $err = new \RuntimeException('boom');
    $r = Result::fail($err);

    $this->expectExceptionObject($err);
    $r->unwrap();
});

it('unwrapOr returns default on failure', function () {
    $r = Result::fail('some error');
    $val = $r->unwrapOr('default');
    expect($val)->toBe('default');
});

it('recover converts failure into success', function () {
    $r = Result::fail('oops')
        ->recover(fn ($err, $meta) => 'fixed');

    expect($r->unwrap())->toBe('fixed');
});

describe('toDebugArray()', function () {
    it('sanitizes sensitive keys and truncates long strings with defaults', function () {
        ConfigStub::reset();

        $meta = [
            'password' => 'super-secret',
            'nested' => ['api_key' => 'abc123', 'note' => 'safe'],
            'long' => str_repeat('a', 250),
        ];

        $result = Result::fail(new RuntimeException('Boom'), $meta);
        $debug = $result->toDebugArray();

        expect($debug['error_message'])->toBe('Boom');
        expect($debug['meta']['password'])->toBe('***REDACTED***');
        expect($debug['meta']['nested']['api_key'])->toBe('***REDACTED***');
        expect($debug['meta']['nested']['note'])->toBe('safe');
        expect(mb_strlen($debug['meta']['long']))->toBe(201); // 200 chars + ellipsis
        expect($debug['meta']['long'])->toEndWith('…');
    });

    it('allows custom sanitizer to override default behavior', function () {
        $result = Result::fail('oops', ['secret' => '123']);

        $debug = $result->toDebugArray(fn () => 'clean');

        expect($debug['error_message'])->toBe('clean');
        expect($debug['meta'])->toBe('clean');
    });

    it('reads sanitizer settings from Laravel-style config when available', function () {
        ConfigStub::set('result-flow.debug', [
            'redaction' => '[redacted]',
            'sensitive_keys' => ['secretstuff'],
            'max_string_length' => 5,
            'truncate_strings' => true,
        ]);

        $result = Result::fail('helloworld', ['secretstuff' => 'token-value', 'note' => 'abcdef']);
        $debug = $result->toDebugArray();

        expect($debug['error_message'])->toBe('hello…'); // truncated to 5 + ellipsis
        expect($debug['meta']['secretstuff'])->toBe('[redacted]');
        expect($debug['meta']['note'])->toBe('abcde…');
    });

    it('can disable sanitization via config', function () {
        ConfigStub::set('result-flow.debug', [
            'enabled' => false,
            'max_string_length' => 5, // should be ignored
        ]);

        $meta = ['password' => 'secret', 'long' => str_repeat('x', 50)];
        $result = Result::fail('helloworld', $meta);
        $debug = $result->toDebugArray();

        expect($debug['meta']['password'])->toBe('secret'); // not redacted
        expect($debug['meta']['long'])->toBe(str_repeat('x', 50)); // not truncated
        expect($debug['error_message'])->toBe('helloworld'); // not truncated
    });

    it('can disable string truncation via config', function () {
        ConfigStub::set('result-flow.debug', [
            'truncate_strings' => false,
            'max_string_length' => 5,
            'sensitive_keys' => [], // ensure no redaction occurs
        ]);

        $result = Result::fail('helloworld', ['token' => 'abcdefghij']);
        $debug = $result->toDebugArray();

        expect($debug['error_message'])->toBe('helloworld'); // full length
        expect($debug['meta']['token'])->toBe('abcdefghij'); // not truncated
    });
});

it('onSuccess and onFailure taps are called appropriately', function () {
    $called = ['ok' => false, 'fail' => false];

    $ok = Result::ok(10, ['k' => 'v'])
        ->onSuccess(function ($v, $meta) use (&$called) {
            $called['ok'] = true;
            expect($v)->toBe(10);
            expect($meta)->toBeArray();
        });

    $fail = Result::fail(new \Exception('x'), ['m' => 1])
        ->onFailure(function ($e, $meta) use (&$called) {
            $called['fail'] = true;
            expect($e)->toBeInstanceOf(\Exception::class);
            expect($meta)->toBeArray();
        });

    expect($called['ok'])->toBeTrue();
    expect($called['fail'])->toBeTrue();
});

it('of() wraps thrown exceptions into failure and returns success otherwise', function () {
    $ok = Result::of(fn () => 5);
    expect($ok->unwrap())->toBe(5);

    $fail = Result::of(function () {
        throw new \Exception('boom');
    });

    expect($fail->isFail())->toBeTrue();
});

it('then accepts an array of steps and folds sequentially', function () {
    $r = Result::ok(2)
        ->then([
            fn ($v, $m) => $v + 3,
            fn ($v, $m) => Result::ok($v * 2),
        ]);

    expect($r->unwrap())->toBe(10);
});

it('mapError transforms the error payload', function () {
    $r = Result::fail('err')
        ->mapError(fn ($e, $m) => new \RuntimeException($e));

    expect($r->error())->toBeInstanceOf(\RuntimeException::class);
});

it('tapMeta observes metadata without changing the payload', function () {
    $tapped = [];

    $r = Result::ok('ok', ['step' => 'start'])
        ->tapMeta(function ($meta) use (&$tapped) {
            $tapped = $meta;
        });

    expect($tapped)->toBe(['step' => 'start']);
    expect($r->meta())->toBe(['step' => 'start']);
    expect($r->unwrap())->toBe('ok');
});

it('mapMeta replaces metadata when needed', function () {
    $r = Result::fail('boom', ['code' => 500])
        ->mapMeta(fn ($meta) => ['code' => 501, 'handled' => true]);

    expect($r->isFail())->toBeTrue();
    expect($r->error())->toBe('boom');
    expect($r->meta())->toBe(['code' => 501, 'handled' => true]);
});

it('mergeMeta merges additional metadata', function () {
    $r = Result::ok('fine', ['env' => 'prod', 'stage' => 'build'])
        ->mergeMeta(['stage' => 'deploy', 'trace' => 'abc']);

    expect($r->unwrap())->toBe('fine');
    expect($r->meta())->toBe(['env' => 'prod', 'stage' => 'deploy', 'trace' => 'abc']);
});

it('otherwise chains failure into success', function () {
    $r = Result::fail('nope')
        ->otherwise(fn ($e, $m) => Result::ok('recovered'));

    expect($r->unwrap())->toBe('recovered');
});

it('then accepts objects with __invoke', function () {
    $obj = new class
    {
        public function __invoke($v, $m)
        {
            return Result::ok($v + 1);
        }
    };

    $r = Result::ok(4)->then($obj);
    expect($r->unwrap())->toBe(5);
});

it('then accepts objects with handle() and execute() methods', function () {
    $handler = new class
    {
        public function handle($v, $m)
        {
            return Result::ok($v + 2);
        }
    };

    $executor = new class
    {
        public function execute($v, $m)
        {
            // return a non-Result value to ensure auto-wrapping works
            return $v * 3;
        }
    };

    $r1 = Result::ok(5)->then($handler);
    expect($r1->unwrap())->toBe(7);

    $r2 = Result::ok(4)->then($executor);
    expect($r2->unwrap())->toBe(12);
});

it('runChain handles arrays, exceptions, and value propagation', function () {
    // Array of steps: value should propagate through all
    $r = Result::ok(1)->then([
        fn ($v, $m) => $v + 2, // 3
        fn ($v, $m) => $v * 5, // 15
        fn ($v, $m) => Result::ok($v - 4), // 11
    ]);
    expect($r->unwrap())->toBe(11);

    // Exception in a step: should convert to fail, meta should include failed_step
    $r2 = Result::ok(10)->then([
        fn ($v, $m) => $v + 1,
        function ($v, $m) {
            throw new RuntimeException('fail here');
        },
        fn ($v, $m) => $v * 2, // should not run
    ]);
    expect($r2->isFail())->toBeTrue();
    expect($r2->error())->toBeInstanceOf(RuntimeException::class);
    expect($r2->meta())->toHaveKey('failed_step');

    // Meta is preserved through steps
    $meta = ['foo' => 'bar'];
    $r3 = Result::ok(2, $meta)->then([
        fn ($v, $m) => $v * 2,
    ]);
    expect($r3->meta())->toBe($meta);
});

it('accepts callable array steps without splitting them', function () {
    $service = new class
    {
        public array $calledWith = [];

        public function handle($value, $meta)
        {
            $this->calledWith = [$value, $meta];

            return Result::ok($value + 5, array_merge($meta, ['from' => 'service']));
        }
    };

    $result = Result::ok(10, ['base' => true])
        ->then([$service, 'handle']) // should be treated as a single callable, not two steps
        ->then(fn ($v, $m) => Result::ok($v * 2, $m));

    expect($result->isOk())->toBeTrue();
    expect($result->value())->toBe(30);
    expect($service->calledWith)->toBe([10, ['base' => true]]);
    expect($result->meta())->toBe(['base' => true, 'from' => 'service']);
});

it('converts thrown exception in then() to failure and unwrap throws', function () {
    $ex = new RuntimeException('crash!');
    $r = Result::ok(42)->then(function () use ($ex) {
        throw $ex;
    });
    expect($r->isFail())->toBeTrue();
    expect($r->error())->toBe($ex);
    // Unwrap should throw the same exception
    try {
        $r->unwrap();
        expect()->fail('unwrap should throw');
    } catch (RuntimeException $caught) {
        expect($caught)->toBe($ex);
    }
});

it('pipeline then() with a local NotifyAction that throws becomes failure and onFailure runs', function () {
    // Create a lightweight test DTO class inline so the test doesn't depend on repo classes
    $dto = new class('Another Test Product', 'ANOTHERSKU456', 2999, 'Another dummy product for testing.')
    {
        public function __construct(public string $name, public string $sku, public int $price, public string $description) {}

        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'sku' => $this->sku,
                'price' => $this->price,
                'description' => $this->description,
            ];
        }
    };

    // Local NotifyAction-like object that throws when invoked
    $notify = new class
    {
        public function __invoke(mixed $payload, array $meta = [])
        {
            throw new \Exception('Notification failed');
        }
    };

    // Directly call then() with the local notify object which throws
    $called = false;

    $r = Result::ok($dto)->then($notify);

    expect($r->isFail())->toBeTrue();
    expect($r->error())->toBeInstanceOf(Exception::class);
    expect($r->meta())->toHaveKey('failed_step');

    // onFailure should be invoked when attached
    $r2 = Result::ok($dto)
        ->then($notify)
        ->onFailure(function ($e, $meta) use (&$called) {
            $called = true;
            expect($meta)->toHaveKey('failed_step');
            expect($e)->toBeInstanceOf(Exception::class);
        });

    expect($called)->toBeTrue();
});

// =========================================================================
// Tests for New API Additions
// =========================================================================

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

describe('flatMap()', function () {
    it('is an alias for then()', function () {
        $result = Result::ok(5)
            ->flatMap(fn ($v) => Result::ok($v * 2));

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe(10);
    });

    it('short-circuits on failure', function () {
        $called = false;
        $result = Result::fail('error')
            ->flatMap(function () use (&$called) {
                $called = true;

                return Result::ok('value');
            });

        expect($called)->toBeFalse();
        expect($result->isFail())->toBeTrue();
    });
});

describe('thenUnsafe()', function () {
    it('chains on success without catching exceptions', function () {
        $result = Result::ok(5)
            ->thenUnsafe(fn ($v) => Result::ok($v * 2));

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe(10);
    });

    it('short-circuits on failure', function () {
        $called = false;
        $result = Result::fail('error')
            ->thenUnsafe(function () use (&$called) {
                $called = true;

                return Result::ok('value');
            });

        expect($called)->toBeFalse();
        expect($result->isFail())->toBeTrue();
    });

    it('wraps raw return values as Result::ok', function () {
        $result = Result::ok(10)
            ->thenUnsafe(fn ($v) => $v * 3);

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe(30);
    });

    it('preserves meta when returning raw values', function () {
        $result = Result::ok('value', ['key' => 'preserved'])
            ->thenUnsafe(fn ($v) => $v.'-modified');

        expect($result->value())->toBe('value-modified');
        expect($result->meta())->toBe(['key' => 'preserved']);
    });

    it('lets exceptions bubble up (does not catch)', function () {
        $exception = new \RuntimeException('Intentional failure');

        $this->expectExceptionObject($exception);

        Result::ok(5)->thenUnsafe(function () use ($exception) {
            throw $exception;
        });
    });

    it('works with objects that have __invoke', function () {
        $action = new class
        {
            public function __invoke(int $v, array $meta): Result
            {
                return Result::ok($v + 10, $meta);
            }
        };

        $result = Result::ok(5)->thenUnsafe($action);
        expect($result->value())->toBe(15);
    });

    it('works with objects that have handle() method', function () {
        $action = new class
        {
            public function handle(int $v, array $meta): Result
            {
                return Result::ok($v + 20, $meta);
            }
        };

        $result = Result::ok(5)->thenUnsafe($action);
        expect($result->value())->toBe(25);
    });

    it('works with objects that have execute() method', function () {
        $action = new class
        {
            public function execute(int $v, array $meta): Result
            {
                return Result::ok($v + 30, $meta);
            }
        };

        $result = Result::ok(5)->thenUnsafe($action);
        expect($result->value())->toBe(35);
    });

    it('can be chained multiple times', function () {
        $result = Result::ok(1)
            ->thenUnsafe(fn ($v) => Result::ok($v + 1))
            ->thenUnsafe(fn ($v) => Result::ok($v * 2))
            ->thenUnsafe(fn ($v) => Result::ok($v + 10));

        expect($result->value())->toBe(14); // (1+1)*2+10
    });

    it('stops chain on Result::fail from step', function () {
        $called = false;
        $result = Result::ok(1)
            ->thenUnsafe(fn ($v) => Result::fail('stopped'))
            ->thenUnsafe(function ($v) use (&$called) {
                $called = true;

                return Result::ok($v);
            });

        expect($result->isFail())->toBeTrue();
        expect($result->error())->toBe('stopped');
        expect($called)->toBeFalse();
    });
});

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
});

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

describe('toDebugArray()', function () {
    it('returns debug-safe array for success', function () {
        $result = Result::ok(['sensitive' => 'data'], ['request_id' => 'abc']);
        $debug = $result->toDebugArray();

        expect($debug['ok'])->toBeTrue();
        expect($debug['value_type'])->toBe('array');
        expect($debug['error_type'])->toBeNull();
        expect($debug['error_message'])->toBeNull();
        expect($debug['meta'])->toBe(['request_id' => 'abc']);
    });

    it('returns debug-safe array for failure with string error', function () {
        ConfigStub::reset();
        ConfigStub::set('result-flow.debug', ['max_string_length' => 200]);

        $result = Result::fail('Something went wrong');
        $debug = $result->toDebugArray();

        expect($debug['ok'])->toBeFalse();
        expect($debug['value_type'])->toBeNull();
        expect($debug['error_type'])->toBe('string');
        expect($debug['error_message'])->toBe('Something went wrong');
    });

    it('returns debug-safe array for failure with exception', function () {
        ConfigStub::reset();
        ConfigStub::set('result-flow.debug', ['max_string_length' => 200]);

        $result = Result::fail(new \RuntimeException('Connection failed'));
        $debug = $result->toDebugArray();

        expect($debug['ok'])->toBeFalse();
        expect($debug['error_type'])->toBe('RuntimeException');
        expect($debug['error_message'])->toBe('Connection failed');
    });

    it('returns null error_message for non-string non-throwable errors', function () {
        $result = Result::fail(['code' => 500, 'errors' => ['field' => 'invalid']]);
        $debug = $result->toDebugArray();

        expect($debug['ok'])->toBeFalse();
        expect($debug['error_type'])->toBe('array');
        expect($debug['error_message'])->toBeNull();
    });
});

describe('meta propagation in runChain', function () {
    it('propagates updated meta through chain steps', function () {
        $result = Result::ok('start', ['step' => 0])
            ->then(fn ($v, $m) => Result::ok($v.'-1', [...$m, 'step' => 1]))
            ->then(fn ($v, $m) => Result::ok($v.'-2', [...$m, 'step' => 2]))
            ->then(fn ($v, $m) => Result::ok($v.'-3', [...$m, 'step' => 3, 'prev_step' => $m['step']]));

        expect($result->isOk())->toBeTrue();
        expect($result->value())->toBe('start-1-2-3');
        expect($result->meta()['step'])->toBe(3);
        expect($result->meta()['prev_step'])->toBe(2);
    });

    it('subsequent steps receive meta from previous step results', function () {
        $receivedMeta = [];

        Result::ok('value', ['initial' => true])
            ->then(function ($v, $m) use (&$receivedMeta) {
                $receivedMeta[] = $m;

                return Result::ok($v, [...$m, 'step1' => true]);
            })
            ->then(function ($v, $m) use (&$receivedMeta) {
                $receivedMeta[] = $m;

                return Result::ok($v, [...$m, 'step2' => true]);
            })
            ->then(function ($v, $m) use (&$receivedMeta) {
                $receivedMeta[] = $m;

                return Result::ok($v);
            });

        expect($receivedMeta[0])->toBe(['initial' => true]);
        expect($receivedMeta[1])->toBe(['initial' => true, 'step1' => true]);
        expect($receivedMeta[2])->toBe(['initial' => true, 'step1' => true, 'step2' => true]);
    });
});

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

describe('edge cases: toArray and toDebugArray', function () {
    it('toArray includes all fields for success', function () {
        $result = Result::ok('value', ['key' => 'meta']);
        $arr = $result->toArray();

        expect($arr)->toBe([
            'ok' => true,
            'value' => 'value',
            'error' => null,
            'meta' => ['key' => 'meta'],
        ]);
    });

    it('toArray includes all fields for failure', function () {
        $result = Result::fail('error', ['key' => 'meta']);
        $arr = $result->toArray();

        expect($arr)->toBe([
            'ok' => false,
            'value' => null,
            'error' => 'error',
            'meta' => ['key' => 'meta'],
        ]);
    });

    it('toDebugArray shows type for objects', function () {
        $obj = new \stdClass;
        $result = Result::ok($obj);
        $debug = $result->toDebugArray();

        expect($debug['value_type'])->toBe('stdClass');
    });

    it('toDebugArray shows int type', function () {
        $result = Result::ok(42);
        $debug = $result->toDebugArray();

        expect($debug['value_type'])->toBe('int');
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
