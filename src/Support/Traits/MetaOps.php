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
     * @param  callable(array<string,mixed>): array<string,mixed>  $map
     * @return Result<TSuccess, TFailure>
     */
    public static function mapMeta(Result $result, callable $map): Result
    {
        return self::withMeta($result, $map($result->meta()));
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  array<string,mixed>  $meta
     * @return Result<TSuccess, TFailure>
     */
    public static function mergeMeta(Result $result, array $meta): Result
    {
        return self::withMeta($result, array_merge($result->meta(), $meta));
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
