<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Traits;

use Maxiviper117\ResultFlow\Result;

/**
 * Metadata manipulation helpers for Result.
 *
 * @internal
 */
final class MetaOps
{
    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(array<string,mixed>): void  $tap
     * @return Result<TSuccess, TFailure>
     */
    public static function tapMeta(Result $result, callable $tap): Result
    {
        $tap($result->meta());

        return $result;
    }

    private static function parameterCount(callable $callable): int
    {
        /** @var \Closure $closure */
        $closure = \Closure::fromCallable($callable);

        $reflection = new \ReflectionFunction($closure);

        return $reflection->getNumberOfParameters();
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @return Result<TSuccess, TFailure>
     */
    public static function mapMeta(Result $result, callable $map): Result
    {
        $meta = $result->meta();

        if ($result->isOk() && self::parameterCount($map) > 1) {
            /** @var array<string,mixed> $mappedMeta */
            $mappedMeta = $map($meta, $result->value());
        } else {
            /** @var array<string,mixed> $mappedMeta */
            $mappedMeta = $map($meta);
        }

        return self::withMeta($result, $mappedMeta);
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  array<string,mixed>|callable  $meta
     * @return Result<TSuccess, TFailure>
     */
    public static function mergeMeta(Result $result, array|callable $meta): Result
    {
        $baseMeta = $result->meta();

        if (is_callable($meta)) {
            $patch = null;

            if ($result->isOk() && self::parameterCount($meta) > 1) {
                /** @var array<string,mixed> $patch */
                $patch = $meta($baseMeta, $result->value());
            } else {
                /** @var array<string,mixed> $patch */
                $patch = $meta($baseMeta);
            }

            return self::withMeta($result, [...$baseMeta, ...$patch]);
        }

        return self::withMeta($result, [...$baseMeta, ...$meta]);
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  array<string,mixed>  $meta
     * @return Result<TSuccess, TFailure>
     */
    private static function withMeta(Result $result, array $meta): Result
    {
        $cloned = $result->isOk()
            ? Result::ok($result->value(), $meta)
            : Result::fail($result->error(), $meta);

        /** @var Result<TSuccess, TFailure> $cloned */
        return $cloned;
    }
}
