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

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @return Result<TSuccess, TFailure>
     */
    public static function mapMeta(Result $result, callable $map): Result
    {
        /** @var array<string,mixed> $mappedMeta */
        $mappedMeta = self::callMetaCallback($result, $map, $result->meta());

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
            /** @var array<string,mixed> $patch */
            $patch = self::callMetaCallback($result, $meta, $baseMeta);

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
        $cloned = $result->isOk() ? Result::ok($result->value(), $meta) : Result::fail($result->error(), $meta);

        /** @var Result<TSuccess, TFailure> $cloned */
        return $cloned;
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable  $callback
     * @param  array<string,mixed>  $meta
     * @return mixed
     */
    private static function callMetaCallback(Result $result, callable $callback, array $meta): mixed
    {
        if (! $result->isOk()) {
            return $callback($meta);
        }

        try {
            return $callback($meta, $result->value());
        } catch (\ArgumentCountError) {
            return $callback($meta);
        }
    }
}
