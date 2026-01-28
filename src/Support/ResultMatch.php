<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support;

use Maxiviper117\ResultFlow\Result;
use Throwable;

/**
 * Pattern-matching helpers for Result values and exceptions.
 *
 * @internal
 */
final class ResultMatch
{
    /**
     * @template TSuccess
     * @template TFailure
     * @template T
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  callable(TSuccess, array<string,mixed>): T  $onSuccess
     * @param  callable(TFailure, array<string,mixed>): T  $onFailure
     * @return T
     */
    public static function match(Result $result, callable $onSuccess, callable $onFailure): mixed
    {
        return $result->isOk()
            ? $onSuccess($result->value(), $result->meta())
            : $onFailure($result->error(), $result->meta());
    }

    /**
     * @template TSuccess
     * @template TFailure
     * @template R
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  array<class-string<Throwable>, callable(Throwable, array<string,mixed>): R>  $exceptionHandlers
     * @param  callable(TSuccess, array<string,mixed>): R  $onSuccess
     * @param  callable(TFailure, array<string,mixed>): R  $onUnhandled
     * @return R
     */
    public static function matchException(
        Result $result,
        array $exceptionHandlers,
        callable $onSuccess,
        callable $onUnhandled,
    ): mixed {
        if ($result->isOk()) {
            return $onSuccess($result->value(), $result->meta());
        }

        $error = $result->error();

        if ($error instanceof Throwable) {
            foreach ($exceptionHandlers as $class => $handler) {
                if ($error instanceof $class) {
                    return $handler($error, $result->meta());
                }
            }
        }

        return $onUnhandled($error, $result->meta());
    }

    /**
     * @template TSuccess
     * @template TFailure
     * @template UFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  array<class-string<Throwable>, callable(Throwable, array<string,mixed>): (Result<TSuccess, UFailure>|TSuccess)>  $handlers
     * @param  null|callable(TFailure, array<string,mixed>): (Result<TSuccess, UFailure>|TSuccess)  $fallback
     * @return Result<TSuccess, UFailure>
     */
    public static function catchException(Result $result, array $handlers, ?callable $fallback = null): Result
    {
        if ($result->isOk()) {
            /** @var Result<TSuccess, UFailure> $result */
            return $result;
        }

        $error = $result->error();

        if ($error instanceof Throwable) {
            foreach ($handlers as $class => $handler) {
                if ($error instanceof $class) {
                    $out = $handler($error, $result->meta());

                    if ($out instanceof Result) {
                        /** @var Result<TSuccess, UFailure> $out */
                        return $out;
                    }

                    /** @var Result<TSuccess, UFailure> */
                    return Result::ok($out, $result->meta());
                }
            }
        }

        if ($fallback !== null) {
            $out = $fallback($error, $result->meta());

            if ($out instanceof Result) {
                /** @var Result<TSuccess, UFailure> $out */
                return $out;
            }

            /** @var Result<TSuccess, UFailure> */
            return Result::ok($out, $result->meta());
        }

        /** @var Result<TSuccess, UFailure> $result @phpstan-ignore varTag.nativeType */
        return $result;
    }
}
