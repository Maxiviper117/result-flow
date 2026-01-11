<?php

use Maxiviper117\ResultFlow\Result;

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
