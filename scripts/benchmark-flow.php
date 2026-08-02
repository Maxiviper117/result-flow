<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

/**
 * @param  callable(): Result<int, never>  $operation
 */
function benchmarkFlowOperation(callable $operation, int $iterations): float
{
    $startedAt = hrtime(true);

    for ($iteration = 0; $iteration < $iterations; $iteration++) {
        $operation();
    }

    return (hrtime(true) - $startedAt) / 1_000_000;
}

/**
 * @return Result<int, never>
 */
function runFlowBenchmark(int $steps, bool $withMetadata): Result
{
    return Result::flow(function () use ($steps, $withMetadata): Generator {
        $value = 0;

        for ($step = 0; $step < $steps; $step++) {
            $value = yield Result::ok(
                $value + 1,
                $withMetadata ? ['flow.step' => $step] : [],
            );
        }

        return $value;
    });
}

/**
 * @return Result<int, never>
 */
function runFluentBenchmark(int $steps, bool $withMetadata): Result
{
    $result = Result::ok(0);

    for ($step = 0; $step < $steps; $step++) {
        $result = $result->then(
            fn (int $value, array $meta): Result => Result::ok(
                $value + 1,
                $withMetadata ? ['flow.step' => $step] : $meta,
            ),
        );
    }

    return $result;
}

$iterations = 1_000;

echo "iterations={$iterations}\n";
echo "steps,metadata,flow_ms,fluent_ms\n";

foreach ([1, 5, 10, 50] as $steps) {
    foreach ([false, true] as $withMetadata) {
        $flowTime = benchmarkFlowOperation(
            fn (): Result => runFlowBenchmark($steps, $withMetadata),
            $iterations,
        );
        $fluentTime = benchmarkFlowOperation(
            fn (): Result => runFluentBenchmark($steps, $withMetadata),
            $iterations,
        );

        printf(
            "%d,%s,%.3f,%.3f\n",
            $steps,
            $withMetadata ? 'yes' : 'no',
            $flowTime,
            $fluentTime,
        );
    }
}
