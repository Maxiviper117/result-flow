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
        /** @var \Closure $closure */
        $closure = \Closure::fromCallable($callback);

        /** @var \ReflectionFunction $ref */
        $ref = new \ReflectionFunction($closure);

        if ($ref->getNumberOfParameters() >= 2) {
            $value = $result->isOk() ? $result->value() : null;

            return $closure($meta, $value);
        }

        return $closure($meta);
    }
}
