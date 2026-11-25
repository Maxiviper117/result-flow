<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Benchmarks;

use Maxiviper117\ResultFlow\Result;

final class CallbackDispatchBench
{
    private Result $success;

    private Result $failure;

    public function init(): void
    {
        $this->success = Result::ok(42, ['trace_id' => 'example']);
        $this->failure = Result::fail('error', ['trace_id' => 'example']);
    }

    /** @BeforeMethods({"init"}) */
    public function benchMap(): void
    {
        $this->success->map(fn (int $value): int => $value + 1);
    }

    /** @BeforeMethods({"init"}) */
    public function benchAndThen(): void
    {
        $this->success->andThen(fn (int $value): Result => Result::ok($value + 1));
    }

    /** @BeforeMethods({"init"}) */
    public function benchSkippedAndThen(): void
    {
        $this->failure->andThen(fn (int $value): Result => Result::ok($value + 1));
    }

    /** @BeforeMethods({"init"}) */
    public function benchMatch(): void
    {
        $this->success->match(fn (int $value): int => $value, fn (string $error): int => 0);
    }
}
