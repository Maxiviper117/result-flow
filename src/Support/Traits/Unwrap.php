<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Traits;

use Maxiviper117\ResultFlow\Result;
use RuntimeException;
use Throwable;

/**
 * Unwrapping helpers for Result values and errors.
 *
 * @internal
 */
final class Unwrap
{
    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @return TSuccess
     *
     * @throws Throwable
     * @throws RuntimeException
     */
    public static function unwrap(Result $result): mixed
    {
        if ($result->isOk()) {
            /** @var TSuccess $value */
            $value = $result->value();

            return $value;
        }
        $err = $result->error();
        if ($err instanceof Throwable) {
            throw $err;
        }
        throw new RuntimeException(is_string($err) ? $err : 'Result failed');
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  TSuccess  $default
     * @return TSuccess
     */
    public static function unwrapOr(Result $result, mixed $default): mixed
    {
        if ($result->isOk()) {
            /** @var TSuccess $value */
            $value = $result->value();

            return $value;
        }

        return $default;
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(TFailure, array<string,mixed>): TSuccess  $fn
     * @return TSuccess
     */
    public static function unwrapOrElse(Result $result, callable $fn): mixed
    {
        if ($result->isOk()) {
            /** @var TSuccess $value */
            $value = $result->value();

            return $value;
        }

        /** @var TFailure $error */
        $error = $result->error();

        return $fn($error, $result->meta());
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(TFailure, array<string,mixed>): Throwable  $exceptionFactory
     * @return TSuccess
     *
     * @throws Throwable
     */
    public static function getOrThrow(Result $result, callable $exceptionFactory): mixed
    {
        if ($result->isOk()) {
            /** @var TSuccess $value */
            $value = $result->value();

            return $value;
        }

        /** @var TFailure $error */
        $error = $result->error();

        throw $exceptionFactory($error, $result->meta());
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @return Result<TSuccess, TFailure>
     *
     * @throws Throwable
     * @throws RuntimeException
     */
    public static function throwIfFail(Result $result): Result
    {
        if ($result->isOk()) {
            return $result;
        }

        $err = $result->error();
        if ($err instanceof Throwable) {
            throw $err;
        }

        throw new RuntimeException(self::stringifyError($err));
    }

    /**
     * Best-effort conversion of an error value to a string.
     */
    private static function stringifyError(mixed $error): string
    {
        if (is_string($error)) {
            return $error;
        }

        try {
            return json_encode($error, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return var_export($error, true);
        }
    }
}
