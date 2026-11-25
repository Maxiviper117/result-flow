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
     * @template R
     * @template T
     * @template E
     * @template F
     *
     * @param  callable(): (Result<R, F>|R)  $acquire
     * @param  callable(R): (Result<T, E>|T)  $use
     * @param  callable(R): void  $release
     * @return Result<T, E|F|Throwable>
     */
    public static function run(callable $acquire, callable $use, callable $release): Result
    {
        try {
            $acquired = $acquire();
        } catch (Throwable $error) {
            /** @var Result<T, E|F|Throwable> */
            return Result::fail($error);
        }

        if ($acquired instanceof Result) {
            if ($acquired->isFail()) {
                /** @var Result<T, E|F|Throwable> $failed */
                $failed = $acquired;

                return $failed;
            }
            /** @var R $resource */
            $resource = $acquired->unwrap();
        } else {
            $resource = $acquired;
        }

        try {
            $out = $use($resource);
            /** @var Result<T, E|F|Throwable> $used */
            $used = $out instanceof Result ? $out : Result::ok($out);
        } catch (Throwable $error) {
            /** @var Result<T, E|F|Throwable> $used */
            $used = Result::fail($error);
        }

        try {
            $release($resource);
        } catch (Throwable $error) {
            if ($used->isFail()) {
                return $used->withMeta(array_merge($used->meta(), ['bracket.release_exception' => $error]));
            }

            /** @var Result<T, E|F|Throwable> */
            return Result::fail($error, $used->meta());
        }

        return $used;
    }
}
