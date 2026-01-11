<?php

use Maxiviper117\ResultFlow\Result;

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

it('overwrites failed_step metadata on failure', function () {
    $result = Result::ok(1, ['failed_step' => 'previous'])
        ->then([
            function () {
                throw new RuntimeException('boom');
            },
        ]);

    expect($result->isFail())->toBeTrue();
    expect($result->meta()['failed_step'])->toBe('Closure');
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
