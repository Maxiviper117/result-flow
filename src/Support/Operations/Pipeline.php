<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Operations;

use InvalidArgumentException;
use Maxiviper117\ResultFlow\Result;
use Throwable;

/**
 * Pipeline execution helpers for chaining Result operations.
 *
 * @internal
 */
final class Pipeline
{
    /**
     * Execute one or more pipeline steps with exception handling.
     *
     * @template TSuccess
     * @template TFailure
     * @template TInput
     * @template TOutput
     * @template TNextFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  (callable(TInput, array<string,mixed>): (Result<TOutput, TNextFailure>|TOutput))|object|array<callable|object>  $next
     * @param  TInput  $input
     * @param  array<string,mixed>  $meta
     * @return Result<TOutput, TNextFailure>
     */
    public static function run(Result $result, callable|object|array $next, mixed $input, array $meta): Result
    {
        // Allow callable arrays like [$service, 'handle'] to be treated as a single step
        $steps = (! is_array($next) || is_callable($next)) ? [$next] : $next;
        /** @var array<callable|object> $steps */
        $acc = $result;
        $current = $input;

        foreach ($steps as $step) {
            try {
                $out = self::invokeStep($step, $current, $meta);
            } catch (Throwable $e) {
                /** @var Result<TOutput, TNextFailure> $failed */
                $failed = Result::fail($e, array_merge($meta, ['failed_step' => self::stepName($step)]));

                return $failed;
            }

            if ($out instanceof Result) {
                /** @var Result<TOutput, TNextFailure> $outResult */
                $outResult = $out;
                $acc = $outResult;
                $meta = $acc->meta(); // Propagate updated meta to subsequent steps
                if ($acc->isFail()) {
                    return $acc;
                }
                $current = $acc->value();
            } else {
                /** @var Result<TOutput, TNextFailure> $acc */
                $acc = Result::ok($out, $meta);
                $current = $out;
            }
        }

        /** @var Result<TOutput, TNextFailure> $accResult */
        $accResult = $acc;

        return $accResult;
    }

    /**
     * Invoke a single pipeline step.
     *
     * @template TInput
     * @template TOutput
     * @template TFailure
     *
     * @param  (callable(TInput, array<string,mixed>): (Result<TOutput, TFailure>|TOutput))|object  $step
     * @param  TInput  $arg
     * @param  array<string,mixed>  $meta
     * @return Result<TOutput, TFailure>|TOutput
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
            $target = $step[0];
            if (is_object($target)) {
                $class = $target::class;
            } elseif (is_string($target)) {
                $class = $target;
            } else {
                $class = get_debug_type($target);
            }

            $method = $step[1];
            if (is_string($method)) {
                $methodName = $method;
            } elseif (is_int($method)) {
                $methodName = (string) $method;
            } else {
                $methodName = get_debug_type($method);
            }

            return $class.'::'.$methodName;
        }

        return 'closure';
    }
}
