<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Traits;

use Maxiviper117\ResultFlow\Result;

/**
 * Transformation helpers for Result values and errors.
 *
 * @internal
 */
final class Transform
{
    /**
     * @template TSuccess
     * @template TFailure
     * @template U
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(TSuccess, array<string,mixed>): U  $map
     * @return Result<U, TFailure>
     */
    public static function map(Result $result, callable $map): Result
    {
        if ($result->isFail()) {
            /** @var Result<U, TFailure> $result */
            return $result;
        }

        /** @var TSuccess $value */
        $value = $result->value();

        return Result::ok($map($value, $result->meta()), $result->meta());
    }

    /**
     * @template TSuccess
     * @template TFailure
     * @template E
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(TFailure, array<string,mixed>): E  $map
     * @return Result<TSuccess, E>
     */
    public static function mapError(Result $result, callable $map): Result
    {
        if ($result->isOk()) {
            /** @var Result<TSuccess, E> $result */
            return $result;
        }

        /** @var TFailure $error */
        $error = $result->error();

        return Result::fail($map($error, $result->meta()), $result->meta());
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(TSuccess, array<string,mixed>): bool  $predicate
     * @param  TFailure|callable(TSuccess, array<string,mixed>): TFailure  $error
     * @return Result<TSuccess, TFailure>
     */
    public static function ensure(Result $result, callable $predicate, mixed $error): Result
    {
        if ($result->isFail()) {
            return $result;
        }

        /** @var TSuccess $value */
        $value = $result->value();

        if ($predicate($value, $result->meta())) {
            return $result;
        }

        $err = (is_callable($error) && ! is_string($error))
            ? $error($value, $result->meta())
            : $error;

        $failed = Result::fail($err, $result->meta());

        /** @var Result<TSuccess, TFailure> $failed */
        return $failed;
    }

    /**
     * @template TSuccess
     * @template TFailure
     * @template U
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(TFailure, array<string,mixed>): U  $fn
     * @return Result<TSuccess|U, never>
     */
    public static function recover(Result $result, callable $fn): Result
    {
        if ($result->isOk()) {
            /** @var TSuccess $value */
            $value = $result->value();

            $ok = Result::ok($value, $result->meta());

            /** @var Result<TSuccess|U, never> $ok */
            return $ok;
        }

        /** @var TFailure $error */
        $error = $result->error();

        $ok = Result::ok($fn($error, $result->meta()), $result->meta());

        /** @var Result<TSuccess|U, never> $ok */
        return $ok;
    }
}
