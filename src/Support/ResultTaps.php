<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support;

use Maxiviper117\ResultFlow\Result;

/**
 * Tap helpers for side-effect callbacks on Result branches.
 *
 * @internal
 */
final class ResultTaps
{
    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(TSuccess|null, TFailure|null, array<string,mixed>): void  $tap
     * @return Result<TSuccess, TFailure>
     */
    public static function tap(Result $result, callable $tap): Result
    {
        $tap($result->value(), $result->error(), $result->meta());

        return $result;
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(TSuccess, array<string,mixed>): void  $tap
     * @return Result<TSuccess, TFailure>
     */
    public static function onSuccess(Result $result, callable $tap): Result
    {
        if ($result->isOk()) {
            $tap($result->value(), $result->meta());
        }

        return $result;
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(TFailure, array<string,mixed>): void  $tap
     * @return Result<TSuccess, TFailure>
     */
    public static function onFailure(Result $result, callable $tap): Result
    {
        if ($result->isFail()) {
            $tap($result->error(), $result->meta());
        }

        return $result;
    }
}
