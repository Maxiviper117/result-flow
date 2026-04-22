<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Traits;

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\ResultError;
use Throwable;

/**
 * Pattern-matching helpers for Result values and exceptions.
 *
 * @internal
 */
final class Matcher
{
    /**
     * @var array<string, int>
     */
    private static array $callableArityCache = [];

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
        if ($result->isOk()) {
            /** @var TSuccess $value */
            $value = $result->value();

            return $onSuccess($value, $result->meta());
        }

        /** @var TFailure $error */
        $error = $result->error();

        return $onFailure($error, $result->meta());
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
            /** @var TSuccess $value */
            $value = $result->value();

            return $onSuccess($value, $result->meta());
        }

        /** @var TFailure $error */
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

        /** @var TFailure $error */
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

    /**
     * Match a Result failure against class-based error handlers.
     *
     * Handlers are checked in array iteration order. The first matching handler
     * whose class-string key matches the failure via instanceof is invoked.
     *
     * If the Result is successful, $onSuccess is invoked.
     * If the failure is not a ResultError, or no handler matches, $onUnhandled is invoked.
     *
     * Error handlers may accept either:
     * - the matched error only
     * - the matched error and metadata
     *
     * Success and unhandled callbacks may accept either:
     * - no arguments
     * - the value/error only
     * - the value/error and metadata
     *
     * @template TSuccess
     * @template TFailure
     * @template TError of ResultError
     * @template R
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  array<class-string<TError>, callable>  $errorHandlers
     * @param  callable(): R|callable(TSuccess): R|callable(TSuccess, array<string, mixed>): R  $onSuccess
     * @param  callable(): R|callable(TFailure): R|callable(TFailure, array<string, mixed>): R  $onUnhandled
     * @return R
     */
    public static function matchError(
        Result $result,
        array $errorHandlers,
        callable $onSuccess,
        callable $onUnhandled,
    ): mixed {
        $meta = $result->meta();

        if ($result->isOk()) {
            /** @var TSuccess $value */
            $value = $result->value();

            return self::invokeMatchCallback($onSuccess, $value, $meta);
        }

        /** @var TFailure $error */
        $error = $result->error();

        if ($error instanceof ResultError) {
            foreach ($errorHandlers as $errorClass => $handler) {
                if ($error instanceof $errorClass) {
                    return self::invokeMatchCallback($handler, $error, $meta);
                }
            }
        }

        return self::invokeMatchCallback($onUnhandled, $error, $meta);
    }

    /**
     * Handle structured ResultError instances and recover to a Result.
     *
     * Similar to catchException, but matches failures by error class name only.
     *
     * Handlers are checked in array iteration order. The first matching handler
     * whose class-string key matches the failure via instanceof is invoked.
     *
     * Handler and fallback callbacks may accept either:
     * - no arguments
     * - the error only
     * - the error and metadata
     *
     * Each callback may return either:
     * - a recovered success value
     * - a Result
     *
     * If no handler matches and no fallback is provided, the original Result is returned.
     *
     * @template TSuccess
     * @template TFailure
     * @template TError of ResultError
     * @template UFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  array<class-string<TError>, callable>  $handlers
     * @param  (callable(): (Result<TSuccess, UFailure>|TSuccess)|callable(TFailure): (Result<TSuccess, UFailure>|TSuccess)|callable(TFailure, array<string, mixed>): (Result<TSuccess, UFailure>|TSuccess))|null  $fallback
     * @return Result<TSuccess, TFailure|UFailure>
     */
    public static function catchError(Result $result, array $handlers, ?callable $fallback = null): Result
    {
        $meta = $result->meta();

        if ($result->isOk()) {
            /** @var Result<TSuccess, TFailure|UFailure> $result */
            return $result;
        }

        /** @var TFailure $error */
        $error = $result->error();

        if ($error instanceof ResultError) {
            foreach ($handlers as $errorClass => $handler) {
                if ($error instanceof $errorClass) {
                    $out = self::invokeCatchCallback($handler, $error, $meta);

                    if ($out instanceof Result) {
                        /** @var Result<TSuccess, TFailure|UFailure> $out */
                        return $out;
                    }

                    /** @var Result<TSuccess, TFailure|UFailure> */
                    return Result::ok($out, $meta);
                }
            }
        }

        if ($fallback !== null) {
            $out = self::invokeCatchCallback($fallback, $error, $meta);

            if ($out instanceof Result) {
                /** @var Result<TSuccess, TFailure|UFailure> $out */
                return $out;
            }

            /** @var Result<TSuccess, TFailure|UFailure> */
            return Result::ok($out, $meta);
        }

        /** @var Result<TSuccess, TFailure|UFailure> $result @phpstan-ignore varTag.nativeType */
        return $result;
    }

    /**
     * @template TValue
     *
     * @param  TValue  $value
     * @param  array<string, mixed>  $meta
     */
    private static function invokeMatchCallback(callable $callback, mixed $value, array $meta): mixed
    {
        $parameterCount = self::callableArity($callback);

        return match (true) {
            $parameterCount <= 0 => $callback(),
            $parameterCount === 1 => $callback($value),
            default => $callback($value, $meta),
        };
    }

    private static function callableArity(callable $callback): int
    {
        $cacheKey = self::callableCacheKey($callback);

        if (isset(self::$callableArityCache[$cacheKey])) {
            return self::$callableArityCache[$cacheKey];
        }

        $arity = self::reflectCallable($callback)->getNumberOfParameters();
        self::$callableArityCache[$cacheKey] = $arity;

        return $arity;
    }

    private static function callableCacheKey(callable $callback): string
    {
        if ($callback instanceof \Closure) {
            $reflection = new \ReflectionFunction($callback);

            return 'closure#'.$reflection->getFileName().':'.$reflection->getStartLine().':'.$reflection->getEndLine();
        }

        if (is_array($callback)) {
            $target = $callback[0] ?? null;
            $method = $callback[1] ?? null;

            if ((is_object($target) || is_string($target)) && is_string($method)) {
                if (is_object($target)) {
                    return 'array#obj:'.$target::class.'::'.$method;
                }

                return 'array#class:'.$target.'::'.$method;
            }

            throw new \InvalidArgumentException('Invalid array callable.');
        }

        if (is_string($callback)) {
            return 'string#'.$callback;
        }

        if (is_object($callback)) {
            return 'invokable#'.$callback::class;
        }

        return 'fallback#'.md5((string) spl_object_id(\Closure::fromCallable($callback)));
    }

    private static function reflectCallable(callable $callback): \ReflectionFunctionAbstract
    {
        if ($callback instanceof \Closure) {
            return new \ReflectionFunction($callback);
        }

        if (is_array($callback)) {
            $target = $callback[0] ?? null;
            $method = $callback[1] ?? null;

            if ((is_object($target) || is_string($target)) && is_string($method)) {
                return new \ReflectionMethod($target, $method);
            }

            throw new \InvalidArgumentException('Invalid array callable.');
        }

        if (is_string($callback) && str_contains($callback, '::')) {
            return new \ReflectionMethod($callback);
        }

        if (is_object($callback)) {
            return new \ReflectionMethod($callback, '__invoke');
        }

        return new \ReflectionFunction(\Closure::fromCallable($callback));
    }

    /**
     * Invoke a flexible catch callback.
     *
     * Supported callback forms:
     * - fn(): mixed
     * - fn(TValue): mixed
     * - fn(TValue, array<string, mixed>): mixed
     *
     * @template TValue
     *
     * @param  TValue  $value
     * @param  array<string, mixed>  $meta
     */
    private static function invokeCatchCallback(callable $callback, mixed $value, array $meta): mixed
    {
        $parameterCount = self::callableArity($callback);

        return match (true) {
            $parameterCount <= 0 => $callback(),
            $parameterCount === 1 => $callback($value),
            default => $callback($value, $meta),
        };
    }
}
