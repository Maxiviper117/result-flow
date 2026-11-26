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
    expect(Result::attempt(fn () => throw $exception, catch: RuntimeException::class)->match(fn ($value) => null, fn ($error) => $error))->toBe($exception)
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
    expect(Result::fail(null)->mapError(fn (...$args) => $args)->match(fn ($value) => null, fn ($error) => $error))->toBe([null]);
});

it('distinguishes every falsy payload by the branch tag', function ($payload) {
    $ok = Result::ok($payload);
    $fail = Result::fail($payload);
    expect($ok->isOk())->toBeTrue()->and($ok->isFail())->toBeFalse()
        ->and($fail->isFail())->toBeTrue()->and($fail->isOk())->toBeFalse()
        ->and($ok->match(fn ($v) => ['ok', $v], fn ($e) => ['fail', $e]))->toBe(['ok', $payload])
        ->and($fail->match(fn ($v) => ['ok', $v], fn ($e) => ['fail', $e]))->toBe(['fail', $payload]);
})->with([null, false, 0, '', [[]]]);

it('skips callbacks on the inactive branch and preserves identity', function () {
    $failed = Result::fail(null, ['trace' => 1]);
    $success = Result::ok(null, ['trace' => 2]);
    $unexpected = fn () => throw new RuntimeException('callback ran');
    expect($failed->map($unexpected))->toBe($failed)
        ->and($failed->andThen($unexpected))->toBe($failed)
        ->and($success->mapError($unexpected))->toBe($success)
        ->and($success->orElse($unexpected))->toBe($success);
});

it('invokes each active transformation and terminal handler exactly once', function () {
    $calls = [];
    $result = Result::ok(2, ['trace' => 1])
        ->map(function ($v) use (&$calls) {
            $calls[] = 'map';

            return $v * 2;
        })
        ->andThen(function ($v) use (&$calls) {
            $calls[] = 'andThen';

            return Result::fail($v, ['child' => 2]);
        })
        ->mapError(function ($e) use (&$calls) {
            $calls[] = 'mapError';

            return $e + 1;
        })
        ->orElse(function ($e) use (&$calls) {
            $calls[] = 'orElse';

            return Result::ok($e);
        });
    $output = $result->match(function ($v) use (&$calls) {
        $calls[] = 'success';

        return $v;
    }, function ($e) use (&$calls) {
        $calls[] = 'failure';

        return $e;
    });
    expect($output)->toBe(5)->and($calls)->toBe(['map', 'andThen', 'mapError', 'orElse', 'success'])
        ->and($result->meta())->toBe(['trace' => 1, 'child' => 2]);
});

it('preserves nested values and replaces nested metadata shallowly', function () {
    $nested = Result::ok('nested');
    $mapped = Result::ok(1)->map(fn () => $nested);
    $chained = Result::ok(1, ['nested' => ['parent' => true], 'keep' => 1])
        ->andThen(fn () => Result::ok(2, ['nested' => ['child' => true], 'keep' => null]));
    expect($mapped->unwrap())->toBe($nested)
        ->and($chained->meta())->toBe(['nested' => ['child' => true], 'keep' => null])
        ->and($chained->withMeta([])->meta())->toBe([]);
});

it('merges metadata on both recovery outcomes', function () {
    foreach ([Result::ok(null, ['overlap' => null]), Result::fail(null, ['overlap' => null])] as $child) {
        $result = Result::fail('parent', ['trace' => 'x', 'overlap' => 'old'])->orElse(fn () => $child);
        expect($result->meta())->toBe(['trace' => 'x', 'overlap' => null]);
    }
});

it('propagates callback exceptions unchanged and rejects non-result chain returns', function () {
    $exception = new LogicException('programming error');
    foreach ([
        fn () => Result::ok(1)->map(fn () => throw $exception),
        fn () => Result::ok(1)->andThen(fn () => throw $exception),
        fn () => Result::fail(1)->mapError(fn () => throw $exception),
        fn () => Result::fail(1)->orElse(fn () => throw $exception),
        fn () => Result::ok(1)->match(fn () => throw $exception, fn () => null),
    ] as $operation) {
        try {
            $operation();
            test()->fail('Expected exception');
        } catch (LogicException $caught) {
            expect($caught)->toBe($exception);
        }
    }
    expect(fn () => Result::ok(1)->andThen(fn () => 1))->toThrow(Error::class);
    expect(fn () => Result::fail(1)->orElse(fn () => 1))->toThrow(Error::class);
});

it('supports callable arrays and invokable actions', function () {
    $action = new class
    {
        public function __invoke(int $value): Result
        {
            return Result::ok($value + 1);
        }

        public function double(int $value): Result
        {
            return Result::ok($value * 2);
        }
    };
    expect(Result::ok(2)->andThen($action)->andThen([$action, 'double'])->unwrap())->toBe(6);
    expect(Result::ok(3)->andThen($action->double(...))->unwrap())->toBe(6);
});

it('unwraps throwables unchanged and preserves eager defaults', function () {
    $error = new RuntimeException('failure');
    try {
        Result::fail($error)->unwrap();
        test()->fail('Expected exception');
    } catch (RuntimeException $caught) {
        expect($caught)->toBe($error);
    }
    expect(Result::fail(null)->unwrapOr('default'))->toBe('default')
        ->and(Result::ok(null)->unwrapOr('default'))->toBeNull();
});

it('exposes exactly the v2 public core API', function () {
    $methods = array_map(fn (ReflectionMethod $method) => $method->name, new ReflectionClass(Result::class)->getMethods(ReflectionMethod::IS_PUBLIC));
    sort($methods);
    $expected = ['ok', 'fail', 'attempt', 'map', 'mapError', 'andThen', 'orElse', 'match', 'isOk', 'isFail', 'unwrap', 'unwrapOr', 'meta', 'withMeta'];
    sort($expected);
    expect($methods)->toBe($expected);
});

it('captures selected exception subclasses once and propagates unrelated errors', function () {
    $calls = 0;
    $selected = new DomainException('selected subtype');
    $result = Result::attempt(function () use (&$calls, $selected) {
        $calls++;
        throw $selected;
    }, catch: LogicException::class);
    expect($calls)->toBe(1)->and($result->match(fn ($value) => null, fn ($error) => $error))->toBe($selected);

    $error = new TypeError('programming error');
    try {
        Result::attempt(fn () => throw $error, catch: RuntimeException::class);
        test()->fail('Expected TypeError to propagate');
    } catch (TypeError $caught) {
        expect($caught)->toBe($error);
    }
});

it('passes only the active failure to match and preserves transform metadata', function () {
    $calls = [];
    $result = Result::fail('bad', ['trace' => 1])->mapError(function (...$args) use (&$calls) {
        $calls[] = $args;

        return strtoupper($args[0]);
    });
    $out = $result->match(fn () => throw new LogicException('inactive'), function (...$args) use (&$calls) {
        $calls[] = $args;

        return $args[0];
    });
    expect($out)->toBe('BAD')->and($calls)->toBe([['bad'], ['BAD']])->and($result->meta())->toBe(['trace' => 1]);
});

it('does not treat empty returned metadata as a reset for either chain operation', function () {
    $success = Result::ok('value', ['trace' => 1])->andThen(fn () => Result::ok('next', []));
    $failure = Result::fail('error', ['trace' => 2])->orElse(fn () => Result::fail('next', []));
    expect($success->meta())->toBe(['trace' => 1])->and($failure->meta())->toBe(['trace' => 2]);
});

it('rejects objects that imitate a Result in fallible callbacks', function () {
    $impostor = new class
    {
        public array $meta = [];

        public function withMeta(array $meta): Result
        {
            return Result::ok('incorrectly accepted', $meta);
        }
    };

    expect(fn () => Result::ok(1)->andThen(fn () => $impostor))
        ->toThrow(TypeError::class, 'The andThen callback must return a Result');
    expect(fn () => Result::fail('bad')->orElse(fn () => $impostor))
        ->toThrow(TypeError::class, 'The orElse callback must return a Result');
});
