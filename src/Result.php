<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow;

use Maxiviper117\ResultFlow\Laravel\ResultResponse;
use Maxiviper117\ResultFlow\Support\Operations\Batch;
use Maxiviper117\ResultFlow\Support\Operations\Pipeline;
use Maxiviper117\ResultFlow\Support\Operations\Retry;
use Maxiviper117\ResultFlow\Support\Output\Debug;
use Maxiviper117\ResultFlow\Support\Output\Serialization;
use Maxiviper117\ResultFlow\Support\Traits\Matcher;
use Maxiviper117\ResultFlow\Support\Traits\MetaOps;
use Maxiviper117\ResultFlow\Support\Traits\Taps;
use Maxiviper117\ResultFlow\Support\Traits\Transform;
use Maxiviper117\ResultFlow\Support\Traits\Unwrap;
use Throwable;

/**
 * A minimal Result type with branch-aware chaining.
 *
 * @template TSuccess The success payload type
 * @template TFailure The failure payload type
 */
final class Result
{
    /**
     * @param  TSuccess|null  $value
     * @param  TFailure|null  $error
     * @param  array<string,mixed>  $meta
     */
    private function __construct(
        private bool $ok,
        private mixed $value,
        private mixed $error,
        private array $meta = [],
    ) {}

    // =========================================================================
    // Static Constructors
    // =========================================================================

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
     * Create a failure result and attach the value that triggered the failure.
     *
     * @template T
     * @template E
     *
     * @param  E  $error
     * @param  T  $failedValue
     * @param  array<string,mixed>  $meta
     * @return Result<T, E>
     */
    public static function failWithValue(mixed $error, mixed $failedValue, array $meta = []): self
    {
        return self::fail($error, array_merge(['failed_value' => $failedValue], $meta));
    }

    /**
     * Wrap a callable and capture exceptions as fail.
     *
     * @template T
     *
     * @param  callable(): T  $fn
     * @return Result<T, Throwable>
     */
    public static function of(callable $fn): self
    {
        try {
            $val = $fn();

            return self::ok($val);
        } catch (Throwable $e) {
            return self::fail($e);
        }
    }

    /**
     * Execute a deferred operation and normalize its output to Result.
     *
     * @template T
     * @template E
     *
     * @param  callable(): (Result<T, E>|T)  $fn
     * @return Result<T, E|Throwable>
     */
    public static function defer(callable $fn): self
    {
        try {
            $value = $fn();

            if ($value instanceof self) {
                /** @var Result<T, E|Throwable> $result */
                $result = $value;

                return $result;
            }

            /** @var Result<T, E|Throwable> $ok */
            $ok = self::ok($value);

            return $ok;
        } catch (Throwable $e) {
            /** @var Result<T, E|Throwable> $failed */
            $failed = self::fail($e);

            return $failed;
        }
    }

    /**
     * Simple retry with optional delay and exponential backoff.
     * For advanced config (jitter, callbacks), use Result::retrier().
     *
     * @param  int  $times  Maximum attempts (min 1)
     * @param  callable(): (Result<mixed, mixed>|mixed)  $fn  Operation to attempt
     * @param  int  $delay  Base delay in milliseconds between attempts
     * @param  bool  $exponential  Use exponential backoff for delays
     * @return Result<mixed, mixed>
     */
    public static function retry(int $times, callable $fn, int $delay = 0, bool $exponential = false): Result
    {
        return Retry::config()
            ->maxAttempts($times)
            ->delay($delay)
            ->exponential($exponential)
            ->attempt($fn);
    }

    /**
     * Retry a deferred operation with retry() semantics.
     *
     * @template T
     * @template E
     *
     * @param  int  $times  Maximum attempts (min 1)
     * @param  callable(): (Result<T, E>|T)  $fn
     * @param  int  $delay  Base delay in milliseconds between attempts
     * @param  bool  $exponential  Use exponential backoff for delays
     * @return Result<T, E|Throwable>
     */
    public static function retryDefer(int $times, callable $fn, int $delay = 0, bool $exponential = false): self
    {
        /** @var Result<T, E|Throwable> $result */
        $result = Retry::config()
            ->maxAttempts($times)
            ->delay($delay)
            ->exponential($exponential)
            ->attempt(fn () => self::defer($fn));

        return $result;
    }

    /**
     * Access the fluent Retry builder for advanced configuration.
     *
     * @return Retry
     *
     * Usage:
     * Result::retrier()
     *     ->maxAttempts(5)
     *     ->jitter(100)
     *     ->attempt(fn() => ...);
     */
    public static function retrier(): Retry
    {
        return Retry::config();
    }

    /**
     * Safely acquire, use, and release a resource.
     *
     * @template R
     * @template T
     * @template E
     *
     * @param  callable(): (Result<R, mixed>|R)  $acquire
     * @param  callable(R): (Result<T, E>|T)  $use
     * @param  callable(R): void  $release
     * @return Result<T, E|Throwable>
     */
    public static function bracket(callable $acquire, callable $use, callable $release): self
    {
        /** @var Result<R, Throwable> $acquired */
        $acquired = self::defer($acquire);

        if ($acquired->isFail()) {
            /** @var Result<T, E|Throwable> $failedAcquire */
            $failedAcquire = self::fail($acquired->error(), $acquired->meta());

            return $failedAcquire;
        }

        /** @var R $resource */
        $resource = $acquired->value();

        /** @var Result<T, E|Throwable> $used */
        $used = self::defer(fn () => $use($resource));

        try {
            $release($resource);
        } catch (Throwable $releaseException) {
            if ($used->isFail()) {
                /** @var Result<T, E|Throwable> $failedWithReleaseMeta */
                $failedWithReleaseMeta = $used->mergeMeta([
                    'bracket.release_exception' => $releaseException,
                ]);

                return $failedWithReleaseMeta;
            }

            /** @var Result<T, E|Throwable> $releaseFailed */
            $releaseFailed = self::fail($releaseException, $used->meta());

            return $releaseFailed;
        }

        return $used;
    }

    /**
     * Combine multiple results into one. Fails on first failure (short-circuit).
     *
     * @template T
     * @template E
     *
     * @param  array<Result<T, E>>  $results
     * @return Result<array<T>, E>
     */
    public static function combine(array $results): self
    {
        /** @var array<T> $values */
        $values = [];
        $mergedMeta = [];

        foreach ($results as $result) {
            if ($result->isFail()) {
                /** @var E $error */
                $error = $result->error();
                $mergedMeta = array_merge($mergedMeta, $result->meta());

                // Return Result<never, E> which is compatible with Result<array<T>, E>
                /** @var Result<array<T>, E> */
                return self::fail($error, $mergedMeta);
            }

            /** @var T $value */
            $value = $result->value();
            $values[] = $value;
            $mergedMeta = array_merge($mergedMeta, $result->meta());
        }

        /** @var Result<array<T>, E> */
        return self::ok($values, $mergedMeta);
    }

    /**
     * Combine results, collecting ALL errors (no short-circuit).
     *
     * @template T
     * @template E
     *
     * @param  array<Result<T, E>>  $results
     * @return Result<array<T>, array<E>>
     */
    public static function combineAll(array $results): self
    {
        /** @var array<T> $values */
        $values = [];
        /** @var array<E> $errors */
        $errors = [];
        $mergedMeta = [];

        foreach ($results as $result) {
            $mergedMeta = array_merge($mergedMeta, $result->meta());

            if ($result->isFail()) {
                /** @var E $error */
                $error = $result->error();
                $errors[] = $error;
            } else {
                /** @var T $value */
                $value = $result->value();
                $values[] = $value;
            }
        }

        if (! empty($errors)) {
            /** @var Result<array<T>, array<E>> */
            return self::fail($errors, $mergedMeta);
        }

        /** @var Result<array<T>, array<E>> */
        return self::ok($values, $mergedMeta);
    }

    /**
     * Map each item to its own Result, preserving input keys.
     *
     * @template TKey of array-key
     * @template TItem
     * @template TMappedSuccess
     * @template TMappedFailure
     *
     * @param  array<TKey, TItem>  $items
     * @param  callable(TItem, TKey): (Result<TMappedSuccess, TMappedFailure>|TMappedSuccess)  $fn
     * @return array<TKey, Result<TMappedSuccess, TMappedFailure|Throwable>>
     */
    public static function mapItems(array $items, callable $fn): array
    {
        return Batch::mapItems($items, $fn);
    }

    /**
     * Map all items and fail on first failure (short-circuit).
     *
     * @template TKey of array-key
     * @template TItem
     * @template TMappedSuccess
     * @template TMappedFailure
     *
     * @param  array<TKey, TItem>  $items
     * @param  callable(TItem, TKey): (Result<TMappedSuccess, TMappedFailure>|TMappedSuccess)  $fn
     * @return Result<array<TKey, TMappedSuccess>, TMappedFailure|Throwable>
     */
    public static function mapAll(array $items, callable $fn): self
    {
        return Batch::mapAll($items, $fn);
    }

    /**
     * Map all items and collect every failure without short-circuiting.
     *
     * @template TKey of array-key
     * @template TItem
     * @template TMappedSuccess
     * @template TMappedFailure
     *
     * @param  array<TKey, TItem>  $items
     * @param  callable(TItem, TKey): (Result<TMappedSuccess, TMappedFailure>|TMappedSuccess)  $fn
     * @return Result<array<TKey, TMappedSuccess>, array<TKey, TMappedFailure|Throwable>>
     */
    public static function mapCollectErrors(array $items, callable $fn): self
    {
        return Batch::mapCollectErrors($items, $fn);
    }

    // =========================================================================
    // State Checking
    // =========================================================================

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

    // =========================================================================
    // Value Access
    // =========================================================================

    /**
     * @return TSuccess|null
     */
    public function value(): mixed
    {
        return $this->value;
    }

    /**
     * @return TFailure|null
     */
    public function error(): mixed
    {
        return $this->error;
    }

    /**
     * @return array<string,mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Convert the Result to an array for debugging / serialization.
     *
     * @return array{ok: bool, value: mixed, error: mixed, meta: array<string,mixed>}
     */
    public function toArray(): array
    {
        return Serialization::toArray($this);
    }

    /**
     * Convert the Result to a debug-safe array (hides sensitive data).
     *
     * @param  callable(mixed): mixed|null  $sanitizer
     * @return array{ok: bool, value_type: string|null, error_type: string|null, error_message: mixed, meta: mixed}
     */
    public function toDebugArray(?callable $sanitizer = null): array
    {
        return Debug::toDebugArray($this, $sanitizer);
    }

    // =========================================================================
    // Metadata Operations
    // =========================================================================

    /**
     * Tap the metadata without changing the result.
     *
     * @param  callable(array<string,mixed>): void  $tap
     * @return Result<TSuccess, TFailure>
     */
    public function tapMeta(callable $tap): self
    {
        return MetaOps::tapMeta($this, $tap);
    }

    /**
     * Transform the metadata.
     *
     * @param  callable(array<string,mixed>): array<string,mixed>  $map
     * @return Result<TSuccess, TFailure>
     */
    public function mapMeta(callable $map): self
    {
        return MetaOps::mapMeta($this, $map);
    }

    /**
     * Merge additional metadata into the result.
     *
     * @param  array<string,mixed>  $meta
     * @return Result<TSuccess, TFailure>
     */
    public function mergeMeta(array $meta): self
    {
        return MetaOps::mergeMeta($this, $meta);
    }

    // =========================================================================
    // Side Effects (Taps)
    // =========================================================================

    /**
     * Tap both branches without changing the result.
     *
     * @param  callable(TSuccess|null, TFailure|null, array<string,mixed>): void  $tap
     * @return Result<TSuccess, TFailure>
     */
    public function tap(callable $tap): self
    {
        return Taps::tap($this, $tap);
    }

    /**
     * Tap the success branch without changing the result.
     *
     * @param  callable(TSuccess, array<string,mixed>): void  $tap
     * @return Result<TSuccess, TFailure>
     */
    public function onSuccess(callable $tap): self
    {
        return Taps::onSuccess($this, $tap);
    }

    /**
     * Alias for onSuccess - Rust convention.
     *
     * @param  callable(TSuccess, array<string,mixed>): void  $tap
     * @return Result<TSuccess, TFailure>
     */
    public function inspect(callable $tap): self
    {
        return $this->onSuccess($tap);
    }

    /**
     * Tap the failure branch without changing the result.
     *
     * @param  callable(TFailure, array<string,mixed>): void  $tap
     * @return Result<TSuccess, TFailure>
     */
    public function onFailure(callable $tap): self
    {
        return Taps::onFailure($this, $tap);
    }

    /**
     * Alias for onFailure - Rust convention.
     *
     * @param  callable(TFailure, array<string,mixed>): void  $tap
     * @return Result<TSuccess, TFailure>
     */
    public function inspectError(callable $tap): self
    {
        return $this->onFailure($tap);
    }

    // =========================================================================
    // Transformations
    // =========================================================================

    /**
     * Map the success value, leaving failure as is.
     *
     * @template U
     *
     * @param  callable(TSuccess, array<string,mixed>): U  $map
     * @return Result<U, TFailure>
     */
    public function map(callable $map): self
    {
        return Transform::map($this, $map);
    }

    /**
     * Map the error, leaving success as is.
     *
     * @template E
     *
     * @param  callable(TFailure, array<string,mixed>): E  $map
     * @return Result<TSuccess, E>
     */
    public function mapError(callable $map): self
    {
        return Transform::mapError($this, $map);
    }

    /**
     * Fail if the predicate returns false on a success value.
     * Useful for inline validation without breaking the chain.
     *
     * @param  callable(TSuccess, array<string,mixed>): bool  $predicate
     * @param  TFailure|callable(TSuccess, array<string,mixed>): TFailure  $error
     * @return Result<TSuccess, TFailure>
     */
    public function ensure(callable $predicate, mixed $error): self
    {
        return Transform::ensure($this, $predicate, $error);
    }

    // =========================================================================
    // Chaining Operations
    // =========================================================================

    /**
     * Chain another action if success.
     *
     * @template U
     *
     * @param  (callable(TSuccess, array<string,mixed>): (Result<U, TFailure>|U))|object|array<callable|object>  $next
     * @return Result<U, TFailure>
     */
    public function then(callable|object|array $next): self
    {
        if (! $this->ok) {
            /** @var Result<U, TFailure> $this @phpstan-ignore varTag.nativeType */
            return $this;
        }

        /** @var TSuccess $value */
        $value = $this->value;

        return Pipeline::run($this, $next, $value, $this->meta);
    }

    /**
     * Alias for then() - standard monadic flatMap/bind.
     *
     * @template U
     *
     * @param  callable(TSuccess, array<string,mixed>): Result<U, TFailure>  $fn
     * @return Result<U, TFailure>
     */
    public function flatMap(callable $fn): self
    {
        return $this->then($fn);
    }

    /**
     * Chain another action if success WITHOUT exception handling.
     *
     * Unlike `then()`, this method does NOT wrap the step in a try/catch.
     * Exceptions will bubble up freely, which is useful for DB transactions
     * where you need the entire transaction to rollback on any failure.
     *
     * **Use Case:** DB transactions that require full rollback on any exception.
     *
     * ```php
     * DB::transaction(function () use ($dto, $meta) {
     *     return Result::ok($dto, $meta)
     *         ->thenUnsafe(new ValidateOrderAction)   // throws bubble → rollback
     *         ->thenUnsafe(new PersistOrderAction)    // throws bubble → rollback
     *         ->throwIfFail();                        // escalate Result::fail to throw
     * });
     * ```
     *
     * @template U
     *
     * @param  (callable(TSuccess, array<string,mixed>): (Result<U, TFailure>|U))|object  $next
     * @return Result<U, TFailure>
     *
     * @throws Throwable Exceptions from the step are NOT caught - they bubble up
     */
    public function thenUnsafe(callable|object $next): self
    {
        if (! $this->ok) {
            /** @var Result<U, TFailure> $this @phpstan-ignore varTag.nativeType */
            return $this;
        }

        /** @var TSuccess $value */
        $value = $this->value;

        $out = Pipeline::invokeStep($next, $value, $this->meta);

        if ($out instanceof self) {
            return $out;
        }

        /** @var Result<U, TFailure> $wrapped */
        $wrapped = self::ok($out, $this->meta);

        return $wrapped;
    }

    /**
     * Chain another action if failure.
     *
     * The `otherwise()` method provides a way to handle failures in a Result chain,
     * similar to how `then()` handles successes. It's analogous to a `catch` block
     * in promise-based or error handling code.
     *
     * **Behavior:**
     * - If the current Result is a **success**, `otherwise()` is skipped entirely
     *   and the success Result is passed through unchanged to the next method in the chain.
     *
     * - If the current Result is a **failure**, the provided callable/action is invoked
     *   with the error payload and metadata. The return value determines what happens next:
     *
     *   • If it returns `Result::ok(...)` → the chain **recovers** from the failure
     *     and subsequent methods treat it as a success
     *
     *   • If it returns `Result::fail(...)` → the failure continues and can be
     *     caught by the next `otherwise()` or `onFailure()` in the chain
     *
     *   • If it returns a plain value → it's wrapped as `Result::ok(value)` (recovery)
     *
     * **Use Cases:**
     * 1. **Recovery/Compensation** - Attempt to fix the error and continue successfully
     *    ```php
     *    ->otherwise(fn($e) => Result::ok($cachedValue))  // fallback to cache
     *    ```
     *
     * 2. **Cleanup with continued failure** - Perform side effects but keep the error
     *    ```php
     *    ->otherwise(function($e) {
     *        Log::error('cleanup', ['error' => $e]);
     *        return Result::fail($e);  // still fails, but we logged it
     *    })
     *    ```
     *
     * 3. **Error transformation** - Convert one error type to another
     *    ```php
     *    ->otherwise(fn($dbError) => Result::fail("User-friendly message"))
     *    ```
     *
     * **Chaining multiple `otherwise()` calls:**
     * Each `otherwise()` in the chain only runs if the previous step was a failure.
     * Once any `otherwise()` returns a success, subsequent `otherwise()` calls are skipped.
     *
     * Example:
     * ```php
     * Result::fail('network error')
     *     ->otherwise(fn($e) => Result::fail('still failing'))  // runs, still fails
     *     ->otherwise(fn($e) => Result::ok('recovered!'))       // runs, recovers
     *     ->otherwise(fn($e) => ...)                            // SKIPPED (previous was ok)
     *     ->then(fn($v) => ...)                                 // runs (we recovered)
     * ```
     *
     * @template U The new error type if the handler returns a different error
     *
     * @param  (callable(TFailure, array<string,mixed>): (Result<TSuccess, U>|TSuccess))|object|array<callable|object>  $next
     * @return Result<TSuccess, U>
     */
    public function otherwise(callable|object|array $next): self
    {
        if ($this->ok) {
            /** @var Result<TSuccess, U> $this @phpstan-ignore varTag.nativeType */
            return $this;
        }

        /** @var TFailure $error */
        $error = $this->error;

        return Pipeline::run($this, $next, $error, $this->meta);
    }

    /**
     * Handle failures by matching the Throwable's class and running the
     * corresponding handler. This is similar to `otherwise()` but focused on
     * matching Throwable types.
     *
     * If the current Result is a success it is returned unchanged.
     * If the error is not a Throwable and no fallback is provided, the
     * original Result is returned unchanged.
     *
     * Handlers may return a Result or a plain value (which will be wrapped
     * as Result::ok).
     *
     * @template UFailure
     *
     * @param  array<class-string<Throwable>, callable(Throwable, array<string,mixed>): (Result<TSuccess, UFailure>|TSuccess)>  $handlers
     * @param  null|callable(TFailure, array<string,mixed>): (Result<TSuccess, UFailure>|TSuccess)  $fallback
     * @return Result<TSuccess, UFailure>
     */
    public function catchException(array $handlers, ?callable $fallback = null): self
    {
        return Matcher::catchException($this, $handlers, $fallback);
    }

    /**
     * Recover from failure by producing a success.
     *
     * @template U
     *
     * @param  callable(TFailure, array<string,mixed>): U  $fn
     * @return Result<TSuccess|U, never>
     */
    public function recover(callable $fn): self
    {
        return Transform::recover($this, $fn);
    }

    // =========================================================================
    // Pattern Matching & Unwrapping
    // =========================================================================

    /**
     * Pattern match on success or failure - forces handling both cases.
     *
     * @template T
     *
     * @param  callable(TSuccess, array<string,mixed>): T  $onSuccess
     * @param  callable(TFailure, array<string,mixed>): T  $onFailure
     * @return T
     */
    public function match(callable $onSuccess, callable $onFailure): mixed
    {
        return Matcher::match($this, $onSuccess, $onFailure);
    }

    /**
     * Pattern match on exceptions when the Result is a failure.
     *
     * Works like `match`, but for Throwable errors based on their class.
     *
     * Order of handling:
     *  - if ok: calls $onSuccess(value, meta)
     *  - if fail and error is Throwable and matches one of the keys: calls matching handler
     *  - otherwise: calls $onUnhandled(error, meta)
     *
     * @template R
     *
     * @param  array<class-string<Throwable>, callable(Throwable, array<string,mixed>): R>  $exceptionHandlers
     * @param  callable(TSuccess, array<string,mixed>): R  $onSuccess
     * @param  callable(TFailure, array<string,mixed>): R  $onUnhandled
     * @return R
     */
    public function matchException(
        array $exceptionHandlers,
        callable $onSuccess,
        callable $onUnhandled,
    ): mixed {
        return Matcher::matchException($this, $exceptionHandlers, $onSuccess, $onUnhandled);
    }

    /**
     * Unwrap the success value or throw the error if it is a Throwable.
     *
     * @return TSuccess
     *
     * @throws Throwable
     * @throws \RuntimeException
     */
    public function unwrap(): mixed
    {
        return Unwrap::unwrap($this);
    }

    /**
     * Provide a default when failed.
     *
     * @param  TSuccess  $default
     * @return TSuccess
     */
    public function unwrapOr(mixed $default): mixed
    {
        return Unwrap::unwrapOr($this, $default);
    }

    /**
     * Unwrap success value or compute default from error lazily.
     *
     * @param  callable(TFailure, array<string,mixed>): TSuccess  $fn
     * @return TSuccess
     */
    public function unwrapOrElse(callable $fn): mixed
    {
        return Unwrap::unwrapOrElse($this, $fn);
    }

    /**
     * Get value or throw a custom exception.
     *
     * @param  callable(TFailure, array<string,mixed>): Throwable  $exceptionFactory
     * @return TSuccess
     *
     * @throws Throwable
     */
    public function getOrThrow(callable $exceptionFactory): mixed
    {
        return Unwrap::getOrThrow($this, $exceptionFactory);
    }

    /**
     * Throw if fail; return $this if ok.
     *
     * Useful to escalate Result failures into exceptions for transaction rollback.
     * This method is chainable - it returns `$this` on success so you can continue
     * the chain.
     *
     * **Use Case:** Force rollback in DB transactions when a step returns Result::fail.
     *
     * ```php
     * DB::transaction(function () use ($dto, $meta) {
     *     return Result::ok($dto, $meta)
     *         ->thenUnsafe(new ValidateOrderAction)->throwIfFail()
     *         ->thenUnsafe(new PersistOrderAction)->throwIfFail()
     *         ->thenUnsafe(new ChargePaymentAction)->throwIfFail();
     * });
     * ```
     *
     * @return Result<TSuccess, TFailure>
     *
     * @throws Throwable If the error is a Throwable, it's thrown directly
     * @throws \RuntimeException If the error is not a Throwable
     */
    public function throwIfFail(): self
    {
        return Unwrap::throwIfFail($this);
    }

    // =========================================================================
    // Output Transformers
    // =========================================================================

    /**
     * Convert the result to JSON.
     *
     * @param  int  $options  JSON encoding options
     *
     * @throws \JsonException
     */
    public function toJson(int $options = 0): string
    {
        return Serialization::toJson($this, $options);
    }

    /**
     * Convert the result to XML.
     */
    public function toXml(string $rootElement = 'result'): string
    {
        return Serialization::toXml($this, $rootElement);
    }

    /**
     * Convert the result to an HTTP response (Laravel-compatible if available).
     */
    public function toResponse(): mixed
    {
        return ResultResponse::toResponse($this);
    }
}
