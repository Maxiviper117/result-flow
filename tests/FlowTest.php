<?php

use Maxiviper117\ResultFlow\Result;

it('sends successful values back into the generator', function () {
    $result = Result::flow(function (): Generator {
        $value = yield Result::ok(10);

        return $value * 2;
    });

    expect($result->isOk())->toBeTrue();
    expect($result->value())->toBe(20);
});

it('composes multiple result operations', function () {
    $result = Result::flow(function (): Generator {
        $user = yield Result::ok(['id' => 7]);
        $account = yield Result::ok(['user_id' => $user['id']]);

        return [
            'user' => $user,
            'account' => $account,
        ];
    });

    expect($result->value())->toBe([
        'user' => ['id' => 7],
        'account' => ['user_id' => 7],
    ]);
});

it('returns the first failure and skips later workflow statements', function () {
    $calls = [];
    $failure = new RuntimeException('user not found');

    $result = Result::flow(function () use (&$calls, $failure): Generator {
        $calls[] = 'before failure';
        yield Result::fail($failure);
        $calls[] = 'after failure';
        yield Result::ok('unreachable');

        return 'unreachable';
    });

    expect($result->isFail())->toBeTrue();
    expect($result->error())->toBe($failure);
    expect($calls)->toBe(['before failure']);
});

it('wraps a plain final value in success', function () {
    $result = Result::flow(function (): Generator {
        if (false) {
            yield Result::ok(null);
        }

        return 42;
    });

    expect($result->isOk())->toBeTrue();
    expect($result->value())->toBe(42);
});

it('supports a final returned Result', function () {
    $final = Result::fail('invoice failed');

    $result = Result::flow(function (): Generator {
        if (false) {
            yield Result::ok(null);
        }

        return Result::fail('invoice failed');
    });

    expect($result->isFail())->toBeTrue();
    expect($result->error())->toBe('invoice failed');

    $sameResult = Result::flow(function () use ($final): Generator {
        if (false) {
            yield Result::ok(null);
        }

        return $final;
    });

    expect($sameResult)->toBe($final);
});

it('supports an empty generator and a null return', function () {
    $empty = Result::flow(function (): Generator {
        if (false) {
            yield Result::ok(null);
        }
    });

    $null = Result::flow(function (): Generator {
        if (false) {
            yield Result::ok(null);
        }

        return null;
    });

    expect($empty->isOk())->toBeTrue();
    expect($empty->value())->toBeNull();
    expect($null->isOk())->toBeTrue();
    expect($null->value())->toBeNull();
});

it('rejects a workflow callback that does not return a Generator', function () {
    expect(fn () => Result::flow(fn () => Result::ok('value')))
        ->toThrow(LogicException::class, 'callback must return a Generator');
});

it('rejects a non-Result yielded value', function () {
    expect(fn () => Result::flow(function (): Generator {
        yield 'not a result';
    }))->toThrow(LogicException::class, 'string yielded');
});

it('rejects a null yielded value', function () {
    expect(fn () => Result::flow(function (): Generator {
        yield null;
    }))->toThrow(LogicException::class, 'null yielded');
});

it('allows exceptions from the workflow to escape', function () {
    $beforeYield = new LogicException('before yield');
    $afterYield = new LogicException('after yield');
    $returnExpression = new LogicException('return expression');

    expect(fn () => Result::flow(function () use ($beforeYield): Generator {
        if ($beforeYield->getMessage() === 'before yield') {
            throw $beforeYield;
        }

        yield Result::ok(null);
    }))->toThrow($beforeYield);

    expect(fn () => Result::flow(function () use ($afterYield): Generator {
        yield Result::ok(null);

        throw $afterYield;
    }))->toThrow($afterYield);

    expect(fn () => Result::flow(function () use ($returnExpression): Generator {
        if (false) {
            yield Result::ok(null);
        }

        return throw $returnExpression;
    }))->toThrow($returnExpression);
});

it('converts workflow exceptions to failures with tryFlow', function () {
    $exception = new LogicException('captured workflow exception');

    $result = Result::tryFlow(function () use ($exception): Generator {
        if ($exception->getMessage() === 'captured workflow exception') {
            throw $exception;
        }

        yield Result::ok(null);
    });

    expect($result->isFail())->toBeTrue();
    expect($result->error())->toBe($exception);
});

it('supports typed binding with yield from', function () {
    $result = Result::flow(function (): Generator {
        $user = yield from Result::bind(Result::ok(['id' => 42]));

        return $user['id'];
    });

    expect($result->isOk())->toBeTrue();
    expect($result->value())->toBe(42);
});

it('short-circuits a failed bound Result', function () {
    $result = Result::flow(function (): Generator {
        return yield from Result::bind(Result::fail('bound failure'));
    });

    expect($result->isFail())->toBeTrue();
    expect($result->error())->toBe('bound failure');
});

it('accumulates metadata from successful yields', function () {
    $result = Result::flow(function (): Generator {
        $first = yield Result::ok('first', ['request_id' => 'r-1', 'step' => 'first']);
        $second = yield Result::ok('second', ['step' => 'second']);

        return [$first, $second];
    });

    expect($result->meta())->toBe([
        'request_id' => 'r-1',
        'step' => 'second',
    ]);
});

it('gives failure metadata priority over earlier success metadata', function () {
    $result = Result::flow(function (): Generator {
        yield Result::ok('value', ['step' => 'success', 'request_id' => 'r-1']);
        yield Result::fail('failed', ['step' => 'failure']);
    });

    expect($result->meta())->toBe([
        'step' => 'failure',
        'request_id' => 'r-1',
    ]);
});

it('gives final Result metadata priority over accumulated metadata', function () {
    $result = Result::flow(function (): Generator {
        yield Result::ok('value', ['step' => 'success', 'request_id' => 'r-1']);

        return Result::ok('done', ['step' => 'final', 'completed' => true]);
    });

    expect($result->meta())->toBe([
        'step' => 'final',
        'request_id' => 'r-1',
        'completed' => true,
    ]);
});

it('runs generator cleanup after success, failure, and exception', function () {
    $cleanup = [];

    $success = Result::flow(function () use (&$cleanup): Generator {
        try {
            yield Result::ok('done');

            return 'done';
        } finally {
            $cleanup[] = 'success';
        }
    });

    $failure = Result::flow(function () use (&$cleanup): Generator {
        try {
            yield Result::fail('failed');
        } finally {
            $cleanup[] = 'failure';
        }
    });

    try {
        Result::flow(function () use (&$cleanup): Generator {
            try {
                yield Result::ok('before exception');
                throw new LogicException('workflow exception');
            } finally {
                $cleanup[] = 'exception';
            }
        });

        expect()->fail('The workflow exception should escape.');
    } catch (LogicException $exception) {
        expect($exception->getMessage())->toBe('workflow exception');
    }

    expect($success->value())->toBe('done');
    expect($failure->error())->toBe('failed');
    expect($cleanup)->toBe(['success', 'failure', 'exception']);
});

it('does not hide an exception from generator cleanup', function () {
    expect(fn () => Result::flow(function (): Generator {
        try {
            yield Result::fail('workflow failure');
        } finally {
            throw new RuntimeException('cleanup failure');
        }
    }))->toThrow(RuntimeException::class, 'cleanup failure');
});

it('runs nested generator cleanup blocks from inner to outer', function () {
    $cleanup = [];

    $result = Result::flow(function () use (&$cleanup): Generator {
        try {
            try {
                yield Result::fail('failed');
            } finally {
                $cleanup[] = 'inner';
            }
        } finally {
            $cleanup[] = 'outer';
        }
    });

    expect($result->isFail())->toBeTrue();
    expect($cleanup)->toBe(['inner', 'outer']);
});
