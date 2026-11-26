<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow;

use Throwable;

/** Acquire, use, and release a resource. */
final class ResourceScope
{
    /**
     * Acquire a resource, use it, and release it exactly once after acquisition.
     * Exceptions are captured; a release failure is attached to an existing
     * use failure or becomes the failure when use succeeded.
     *
     * @template TAcquire
     * @template TUse
     *
     * @param  callable(): TAcquire  $acquire
     * @param  callable((TAcquire is Result<mixed, mixed> ? template-type<TAcquire, Result, 'TSuccess'> : TAcquire)): TUse  $use
     * @param  callable((TAcquire is Result<mixed, mixed> ? template-type<TAcquire, Result, 'TSuccess'> : TAcquire)): void  $release
     * @return Result<(TUse is Result<mixed, mixed> ? template-type<TUse, Result, 'TSuccess'> : TUse), (TUse is Result<mixed, mixed> ? template-type<TUse, Result, 'TFailure'> : never)|(TAcquire is Result<mixed, mixed> ? template-type<TAcquire, Result, 'TFailure'> : never)|Throwable>
     */
    public static function run(callable $acquire, callable $use, callable $release): Result
    {
        try {
            $acquired = $acquire();
        } catch (Throwable $error) {
            return Result::fail($error);
        }

        if ($acquired instanceof Result) {
            if ($acquired->isFail()) {
                return $acquired;
            }
            $resource = $acquired->unwrap();
        } else {
            $resource = $acquired;
        }

        try {
            $out = $use($resource);
            $used = $out instanceof Result ? $out : Result::ok($out);
        } catch (Throwable $error) {
            $used = Result::fail($error);
        }

        try {
            $release($resource);
        } catch (Throwable $error) {
            if ($used->isFail()) {
                return $used->withMeta(array_merge($used->meta(), ['bracket.release_exception' => $error]));
            }

            return Result::fail($error, $used->meta());
        }

        return $used;
    }
}
