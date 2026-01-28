<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support;

use InvalidArgumentException;
use Maxiviper117\ResultFlow\Result;
use Throwable;

/**
 * Pipeline execution helpers for chaining Result operations.
 *
 * @internal
 */
final class ResultPipeline
{
    /**
     * Execute one or more pipeline steps with exception handling.
     *
     * @param  callable|object|array<callable|object>  $next
     * @param  array<string,mixed>  $meta
     * @return Result<mixed, mixed>
     */
    public static function run(Result $result, callable|object|array $next, mixed $input, array $meta): Result
    {
        // Allow callable arrays like [$service, 'handle'] to be treated as a single step
        $steps = (! is_array($next) || is_callable($next)) ? [$next] : $next;

        $acc = $result;
        $current = $input;

        foreach ($steps as $step) {
            try {
                $out = self::invokeStep($step, $current, $meta);
            } catch (Throwable $e) {
                return Result::fail($e, array_merge($meta, ['failed_step' => self::stepName($step)]));
            }

            if ($out instanceof Result) {
                $acc = $out;
                $meta = $acc->meta(); // Propagate updated meta to subsequent steps
                if ($acc->isFail()) {
                    return $acc;
                }
                $current = $acc->value();
            } else {
                $acc = Result::ok($out, $meta);
                $current = $out;
            }
        }

        return $acc;
    }

    /**
     * Invoke a single pipeline step.
     *
     * @param  array<string,mixed>  $meta
     *
     * @throws InvalidArgumentException
     */
    public static function invokeStep(callable|object $step, mixed $arg, array $meta): mixed
    {
        if (is_callable($step)) {
            return $step($arg, $meta);
        }

        if (method_exists($step, 'handle')) {
            return $step->handle($arg, $meta);
        }

        if (method_exists($step, 'execute')) {
            return $step->execute($arg, $meta);
        }

        throw new InvalidArgumentException(
            sprintf(
                'Step of type %s is not callable and has no handle() or execute() method.',
                $step::class
            )
        );
    }

    /**
     * Best effort human friendly name for error context.
     */
    private static function stepName(callable|object $step): string
    {
        if (is_object($step)) {
            return $step::class;
        }
        if (is_array($step) && isset($step[0], $step[1])) {
            return (is_object($step[0]) ? $step[0]::class : (string) $step[0]).'::'.(string) $step[1];
        }

        return 'closure';
    }
}
