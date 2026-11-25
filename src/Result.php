<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow;

use Throwable;

/**
 * A synchronous, tagged success or failure with shallow metadata.
 *
 * @template-covariant TSuccess
 * @template-covariant TFailure
 */
final class Result
{
    /**
     * @param  TSuccess|null  $value
     * @param  TFailure|null  $error
     * @param  array<string,mixed>  $meta
     */
    private function __construct(
        private readonly bool $ok,
        private readonly mixed $value,
        private readonly mixed $error,
        private readonly array $meta = [],
    ) {
    }

    /**
     * Create a success result.
     *
     * @template T
     *
     * @param  T  $value
     * @param  array<string,mixed>  $meta
     * @return Result<T, never>
     */
    public static function ok(mixed $value, array $meta = []): self
    {
        /** @var Result<T, never> */
        return new self(true, $value, null, $meta);
    }

    /**
     * Create a failure result.
     *
     * @template E
     *
     * @param  E  $error
     * @param  array<string,mixed>  $meta
     * @return Result<never, E>
     */
    public static function fail(mixed $error, array $meta = []): self
    {
        /** @var Result<never, E> */
        return new self(false, null, $error, $meta);
    }

    /**
     * Execute a value-producing callback, capturing only the selected exception type.
     *
     * @template T
     * @template E of Throwable
     *
     * @param  callable(): T  $fn
     * @param  class-string<E>  $catch
     * @return Result<T, E>
     */
    public static function attempt(callable $fn, string $catch): self
    {
        try {
            return self::ok($fn());
        } catch (Throwable $error) {
            if (! $error instanceof $catch) {
                throw $error;
            }

            return self::fail($error);
        }
    }

    /**
     * Check whether the result represents success.
     */
    public function isOk(): bool
    {
        return $this->ok;
    }

    /**
     * Check whether the result represents failure.
     */
    public function isFail(): bool
    {
        return ! $this->ok;
    }

    /**
     * @return array<string,mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Replace the metadata bag.
     *
     * @param  array<string, mixed>  $meta
     * @return Result<TSuccess, TFailure>
     */
    public function withMeta(array $meta): self
    {
        return new self($this->ok, $this->value, $this->error, $meta);
    }

    /**
     * Map the success value, leaving failure as is.
     *
     * @template U
     *
     * @param  callable(TSuccess): U  $map
     * @return Result<U, TFailure>
     */
    public function map(callable $map): self
    {
        if (! $this->ok) {
            /** @var Result<U, TFailure> $this @phpstan-ignore varTag.nativeType */
            return $this;
        }

        /** @var TSuccess $value */
        $value = $this->value;

        return self::ok($map($value), $this->meta);
    }

    /**
     * Map the error, leaving success as is.
     *
     * @template E
     *
     * @param  callable(TFailure): E  $map
     * @return Result<TSuccess, E>
     */
    public function mapError(callable $map): self
    {
        if ($this->ok) {
            /** @var Result<TSuccess, E> $this @phpstan-ignore varTag.nativeType */
            return $this;
        }

        /** @var TFailure $error */
        $error = $this->error;

        return self::fail($map($error), $this->meta);
    }

    /**
     * @template U
     * @template E
     *
     * @param  callable(TSuccess): Result<U, E>  $fn
     * @return Result<U, TFailure|E>
     */
    public function andThen(callable $fn): self
    {
        if (! $this->ok) {
            /** @var Result<U, TFailure|E> $this @phpstan-ignore varTag.nativeType */
            return $this;
        }

        /** @var TSuccess $value */
        $value = $this->value;
        $result = $fn($value);

        /** @var Result<U, TFailure|E> $merged */
        $merged = $result->withMeta(array_merge($this->meta, $result->meta));

        return $merged;
    }

    /**
     * @template U
     * @template E
     *
     * @param  callable(TFailure): Result<U, E>  $fn
     * @return Result<TSuccess|U, E>
     */
    public function orElse(callable $fn): self
    {
        if ($this->ok) {
            /** @var Result<TSuccess|U, E> $this @phpstan-ignore varTag.nativeType */
            return $this;
        }

        /** @var TFailure $error */
        $error = $this->error;
        $result = $fn($error);

        /** @var Result<TSuccess|U, E> $merged */
        $merged = $result->withMeta(array_merge($this->meta, $result->meta));

        return $merged;
    }

    /**
     * Pattern match on success or failure - forces handling both cases.
     *
     * @template U
     * @template V
     *
     * @param  callable(TSuccess): U  $onSuccess
     * @param  callable(TFailure): V  $onFailure
     * @return U|V
     */
    public function match(callable $onSuccess, callable $onFailure): mixed
    {
        if ($this->ok) {
            /** @var TSuccess $value */
            $value = $this->value;

            return $onSuccess($value);
        }

        /** @var TFailure $error */
        $error = $this->error;

        return $onFailure($error);
    }

    /**
     * Unwrap the success value or throw the error if it is a Throwable.
     *
     * @return TSuccess
     *
     * @throws Throwable
     * @throws UnwrapFailure
     */
    public function unwrap(): mixed
    {
        if ($this->ok) {
            /** @var TSuccess $value */
            $value = $this->value;

            return $value;
        }

        if ($this->error instanceof Throwable) {
            throw $this->error;
        }

        throw new UnwrapFailure($this->error);
    }

    /**
     * Provide a default when failed.
     *
     * @template U
     *
     * @param  U  $default
     * @return TSuccess|U
     */
    public function unwrapOr(mixed $default): mixed
    {
        if (! $this->ok) {
            return $default;
        }

        /** @var TSuccess $value */
        $value = $this->value;

        return $value;
    }
}
