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
     * @var array<string, int>
     */
    private static array $callableArityCache = [];

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  (callable(array<string,mixed>): mixed)|(callable(array<string,mixed>, TSuccess|null): mixed)  $tap
     * @return Result<TSuccess, TFailure>
     */
    public static function tapMeta(Result $result, callable $tap): Result
    {
        // Allow tap callbacks to accept either (meta) or (meta, value) just like
        // the other meta helpers. We ignore the return value.
        self::callMetaCallback($result, $tap, $result->meta());

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
     * @param  array<string,mixed>|(callable(array<string,mixed>): array<string,mixed>)|(callable(array<string,mixed>, TSuccess|null): array<string,mixed>)  $meta
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
     * Invokes a metadata callback that can optionally receive the result value.
     *
     * Supported callback signatures:
     * - fn(array<string,mixed> $meta): mixed
     * - fn(array<string,mixed> $meta, TSuccess|null $value): mixed
     *
     * If the callback expects two or more parameters, the result value is passed
     * as the second argument. For failed results, `null` is provided. This means
     * `Ok(null)` and `Fail(...)` are indistinguishable from the callback’s perspective.
     *
     * The callable is converted to a Closure to enable reflection-based arity detection.
     *
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @param  (callable(array<string,mixed>): mixed)|(callable(array<string,mixed>, TSuccess|null): mixed)  $callback
     * @param  array<string,mixed>  $meta
     */
    private static function callMetaCallback(Result $result, callable $callback, array $meta): mixed
    {
        if (self::callableArity($callback) >= 2) {
            $value = $result->isOk() ? $result->value() : null;

            return $callback($meta, $value);
        }

        return $callback($meta);
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
}
