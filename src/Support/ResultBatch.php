<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support;

use Maxiviper117\ResultFlow\Result;
use Throwable;

/**
 * Batch helpers for mapping item collections through Result callbacks.
 *
 * @internal
 */
final class ResultBatch
{
    /**
     * @template TKey of array-key
     * @template TItem
     * @template TSuccess
     * @template TFailure
     *
     * @param  array<TKey, TItem>  $items
     * @param  callable(TItem, TKey): (Result<TSuccess, TFailure>|TSuccess)  $fn
     * @return array<TKey, Result<TSuccess, TFailure|Throwable>>
     */
    public static function mapItems(array $items, callable $fn): array
    {
        $results = [];

        foreach ($items as $key => $item) {
            $results[$key] = self::mapOne($fn, $item, $key);
        }

        /** @var array<TKey, Result<TSuccess, TFailure|Throwable>> $results */
        return $results;
    }

    /**
     * @template TKey of array-key
     * @template TItem
     * @template TSuccess
     * @template TFailure
     *
     * @param  array<TKey, TItem>  $items
     * @param  callable(TItem, TKey): (Result<TSuccess, TFailure>|TSuccess)  $fn
     * @return Result<array<TKey, TSuccess>, TFailure|Throwable>
     */
    public static function mapAll(array $items, callable $fn): Result
    {
        $values = [];
        $mergedMeta = [];

        foreach ($items as $key => $item) {
            $result = self::mapOne($fn, $item, $key);
            $mergedMeta = array_merge($mergedMeta, $result->meta());

            if ($result->isFail()) {
                /** @var TFailure|Throwable $error */
                $error = $result->error();

                /** @var Result<array<TKey, TSuccess>, TFailure|Throwable> */
                return Result::fail($error, $mergedMeta);
            }

            /** @var TSuccess $value */
            $value = $result->value();
            $values[$key] = $value;
        }

        /** @var Result<array<TKey, TSuccess>, TFailure|Throwable> */
        return Result::ok($values, $mergedMeta);
    }

    /**
     * @template TKey of array-key
     * @template TItem
     * @template TSuccess
     * @template TFailure
     *
     * @param  array<TKey, TItem>  $items
     * @param  callable(TItem, TKey): (Result<TSuccess, TFailure>|TSuccess)  $fn
     * @return Result<array<TKey, TSuccess>, array<TKey, TFailure|Throwable>>
     */
    public static function mapCollectErrors(array $items, callable $fn): Result
    {
        $values = [];
        $errors = [];
        $mergedMeta = [];

        foreach ($items as $key => $item) {
            $result = self::mapOne($fn, $item, $key);
            $mergedMeta = array_merge($mergedMeta, $result->meta());

            if ($result->isFail()) {
                /** @var TFailure|Throwable $error */
                $error = $result->error();
                $errors[$key] = $error;

                continue;
            }

            /** @var TSuccess $value */
            $value = $result->value();
            $values[$key] = $value;
        }

        if ($errors !== []) {
            /** @var Result<array<TKey, TSuccess>, array<TKey, TFailure|Throwable>> */
            return Result::fail($errors, $mergedMeta);
        }

        /** @var Result<array<TKey, TSuccess>, array<TKey, TFailure|Throwable>> */
        return Result::ok($values, $mergedMeta);
    }

    /**
     * @template TKey of array-key
     * @template TItem
     * @template TSuccess
     * @template TFailure
     *
     * @param  callable(TItem, TKey): (Result<TSuccess, TFailure>|TSuccess)  $fn
     * @param  TItem  $item
     * @param  TKey  $key
     * @return Result<TSuccess, TFailure|Throwable>
     */
    private static function mapOne(callable $fn, mixed $item, int|string $key): Result
    {
        try {
            $out = $fn($item, $key);

            if ($out instanceof Result) {
                /** @var Result<TSuccess, TFailure|Throwable> $out */
                return $out;
            }

            /** @var Result<TSuccess, TFailure|Throwable> */
            return Result::ok($out);
        } catch (Throwable $e) {
            /** @var Result<TSuccess, TFailure|Throwable> */
            return Result::fail($e);
        }
    }
}
