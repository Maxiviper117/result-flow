<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow;

/** Operations over keyed collections of Results. */
final class ResultBatch
{
    /**
     * Return the first success or the last failure; the input must not be empty.
     * Metadata from visited Results is merged in order.
     *
     * @template T
     * @template E
     *
     * @param  array<array-key, Result<T, E>>  $results
     * @return Result<T, E>
     */
    public static function firstSuccess(array $results): Result
    {
        if ($results === []) {
            throw new \InvalidArgumentException('firstSuccess requires at least one Result');
        }
        $meta = [];
        foreach ($results as $result) {
            $meta = array_merge($meta, $result->meta());
            if ($result->isOk()) {
                return $result->withMeta($meta);
            }
        }

        return $result->withMeta($meta);
    }

    /**
     * Combine two already-evaluated Results, returning the first failure.
     *
     * @template T
     * @template U
     * @template E
     * @template F
     *
     * @param  Result<T, E>  $first
     * @param  Result<U, F>  $second
     * @return Result<array{T, U}, E|F>
     */
    public static function zip(Result $first, Result $second): Result
    {
        return $first->andThen(fn ($left) => $second->map(fn ($right) => [$left, $right]));
    }

    /**
     * Combine already-evaluated Results, returning the first failure.
     *
     * @template K of array-key
     * @template T
     * @template E
     *
     * @param  array<K, Result<T, E>>  $results
     * @return Result<array<K, T>, E>
     */
    public static function firstFailure(array $results): Result
    {
        $values = [];
        $meta = [];
        foreach ($results as $key => $result) {
            $meta = array_merge($meta, $result->meta());
            if ($result->isFail()) {
                /** @var E $error */
                $error = $result->match(fn ($value) => null, fn ($error) => $error);

                /** @var Result<array<K, T>, E> */
                return Result::fail($error, $meta);
            }
            $values[$key] = $result->unwrap();
        }

        /** @var Result<array<K, T>, E> */
        return Result::ok($values, $meta);
    }

    /**
     * Combine already-evaluated Results, collecting every failure by key.
     *
     * @template K of array-key
     * @template T
     * @template E
     *
     * @param  array<K, Result<T, E>>  $results
     * @return Result<array<K, T>, array<K, E>>
     */
    public static function allErrors(array $results): Result
    {
        $values = [];
        $errors = [];
        $meta = [];
        foreach ($results as $key => $result) {
            $meta = array_merge($meta, $result->meta());
            if ($result->isFail()) {
                /** @var E $error */
                $error = $result->match(fn ($value) => null, fn ($error) => $error);
                $errors[$key] = $error;
            } else {
                $values[$key] = $result->unwrap();
            }
        }

        if ($errors !== []) {
            /** @var Result<array<K, T>, array<K, E>> */
            return Result::fail($errors, $meta);
        }

        /** @var Result<array<K, T>, array<K, E>> */
        return Result::ok($values, $meta);
    }

    /**
     * Evaluate items until the first failure. Exceptions propagate unchanged.
     *
     * @template K of array-key
     * @template I
     * @template T
     * @template E
     *
     * @param  array<K, I>  $items
     * @param  callable(I, K): Result<T, E>  $fn
     * @return Result<array<K, T>, E>
     */
    public static function mapFirstFailure(array $items, callable $fn): Result
    {
        $results = [];
        foreach ($items as $key => $item) {
            $results[$key] = $fn($item, $key);
            if ($results[$key]->isFail()) {
                break;
            }
        }

        return self::firstFailure($results);
    }

    /**
     * Evaluate every item and collect all failures. Exceptions propagate unchanged.
     *
     * @template K of array-key
     * @template I
     * @template T
     * @template E
     *
     * @param  array<K, I>  $items
     * @param  callable(I, K): Result<T, E>  $fn
     * @return Result<array<K, T>, array<K, E>>
     */
    public static function mapAllErrors(array $items, callable $fn): Result
    {
        $results = [];
        foreach ($items as $key => $item) {
            $results[$key] = $fn($item, $key);
        }

        return self::allErrors($results);
    }
}
