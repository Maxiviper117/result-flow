<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow;

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

    // =========================================================================
    // State Checking
    // =========================================================================

    public function isOk(): bool
    {
        return $this->ok;
    }

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
        return [
            'ok' => $this->ok,
            'value' => $this->value,
            'error' => $this->error,
            'meta' => $this->meta,
        ];
    }

    /**
     * Convert the Result to a debug-safe array (hides sensitive data).
     *
     * @param  callable(mixed): mixed|null  $sanitizer
     * @return array{ok: bool, value_type: string|null, error_type: string|null, error_message: mixed, meta: mixed}
     */
    public function toDebugArray(?callable $sanitizer = null): array
    {
        $sanitizer = $sanitizer ?? [self::class, 'defaultSanitizer'];

        return [
            'ok' => $this->ok,
            'value_type' => $this->ok ? get_debug_type($this->value) : null,
            'error_type' => ! $this->ok ? get_debug_type($this->error) : null,
            'error_message' => ! $this->ok && $this->error instanceof Throwable
                ? $sanitizer($this->error->getMessage())
                : (! $this->ok && is_string($this->error) ? $sanitizer($this->error) : null),
            'meta' => $sanitizer($this->meta),
        ];
    }

    private static function defaultSanitizer(mixed $value): mixed
    {
        // Pull overrides from Laravel config if available; fall back to hardcoded defaults.
        $debugConfig = self::debugConfig();
        $enabled = ($debugConfig['enabled'] ?? true) === true;
        $redaction = $debugConfig['redaction'] ?? '***REDACTED***';
        $sensitiveKeys = $debugConfig['sensitive_keys'] ?? ['password', 'pass', 'secret', 'token', 'api_key', 'apikey', 'ssn', 'card', 'authorization'];
        $max = is_int($debugConfig['max_string_length'] ?? null) ? $debugConfig['max_string_length'] : 200;
        $truncateStrings = ($debugConfig['truncate_strings'] ?? true) === true;

        if (! $enabled) {
            return $value;
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $lowerKey = is_string($k) ? strtolower($k) : '';
                $isSensitive = false;
                foreach ($sensitiveKeys as $s) {
                    if ($s !== '' && str_contains($lowerKey, $s)) {
                        $isSensitive = true;
                        break;
                    }
                }
                if ($isSensitive) {
                    $out[$k] = $redaction;
                } else {
                    $out[$k] = self::defaultSanitizer($v);
                }
            }

            return $out;
        }

        if (is_string($value)) {
            // Truncate very long strings (tokens, dumps) to avoid leaking full contents.
            if ($truncateStrings && self::stringLength($value) > $max) {
                return self::stringSlice($value, 0, $max).'…';
            }

            return $value;
        }

        return $value;
    }

    /**
     * Fetch debug config from Laravel if the helper is available; otherwise return defaults.
     *
     * @return array{enabled?: bool, redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int, truncate_strings?: bool}
     */
    private static function debugConfig(): array
    {
        if (function_exists('config')) {
            /** @var array{redaction?: string, sensitive_keys?: array<int,string>, max_string_length?: int}|null $config */
            $config = config('result-flow.debug');

            if (is_array($config)) {
                return $config;
            }
        }

        return [];
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
        $tap($this->meta);

        return $this;
    }

    /**
     * Transform the metadata.
     *
     * @param  callable(array<string,mixed>): array<string,mixed>  $map
     * @return Result<TSuccess, TFailure>
     */
    public function mapMeta(callable $map): self
    {
        return $this->withMeta($map($this->meta));
    }

    /**
     * Merge additional metadata into the result.
     *
     * @param  array<string,mixed>  $meta
     * @return Result<TSuccess, TFailure>
     */
    public function mergeMeta(array $meta): self
    {
        return $this->withMeta(array_merge($this->meta, $meta));
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
        $tap($this->value, $this->error, $this->meta);

        return $this;
    }

    /**
     * Tap the success branch without changing the result.
     *
     * @param  callable(TSuccess, array<string,mixed>): void  $tap
     * @return Result<TSuccess, TFailure>
     */
    public function onSuccess(callable $tap): self
    {
        if ($this->ok) {
            $tap($this->value, $this->meta);
        }

        return $this;
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
        if (! $this->ok) {
            $tap($this->error, $this->meta);
        }

        return $this;
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
        if (! $this->ok) {
            /** @var Result<U, TFailure> $this @phpstan-ignore varTag.nativeType */
            return $this;
        }

        return self::ok($map($this->value, $this->meta), $this->meta);
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
        if ($this->ok) {
            /** @var Result<TSuccess, E> $this @phpstan-ignore varTag.nativeType */
            return $this;
        }

        return self::fail($map($this->error, $this->meta), $this->meta);
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
        if (! $this->ok) {
            return $this;
        }

        if ($predicate($this->value, $this->meta)) {
            return $this;
        }

        $err = (is_callable($error) && ! is_string($error))
            ? $error($this->value, $this->meta)
            : $error;

        return self::fail($err, $this->meta);
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

        return $this->runChain($next, $this->value, $this->meta);
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

        $out = $this->invokeStep($next, $this->value, $this->meta);

        if ($out instanceof self) {
            return $out;
        }

        return self::ok($out, $this->meta);
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

        return $this->runChain($next, $this->error, $this->meta);
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
        if ($this->ok) {
            /** @var Result<TSuccess, UFailure> $this @phpstan-ignore varTag.nativeType */
            return $this;
        }

        $error = $this->error;

        if ($error instanceof Throwable) {
            foreach ($handlers as $class => $handler) {
                if ($error instanceof $class) {
                    $out = $handler($error, $this->meta);

                    if ($out instanceof self) {
                        /** @var Result<TSuccess, UFailure> $out */
                        return $out;
                    }

                    /** @var Result<TSuccess, UFailure> */
                    return self::ok($out, $this->meta);
                }
            }
        }

        if ($fallback !== null) {
            $out = $fallback($error, $this->meta);

            if ($out instanceof self) {
                /** @var Result<TSuccess, UFailure> $out */
                return $out;
            }

            /** @var Result<TSuccess, UFailure> */
            return self::ok($out, $this->meta);
        }

        /** @var Result<TSuccess, UFailure> $this @phpstan-ignore varTag.nativeType */
        return $this;
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
        if ($this->ok) {
            /** @var Result<TSuccess|U, never> $this @phpstan-ignore varTag.nativeType */
            return $this;
        }

        return self::ok($fn($this->error, $this->meta), $this->meta); // @phpstan-ignore return.type
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
        return $this->ok
            ? $onSuccess($this->value, $this->meta)
            : $onFailure($this->error, $this->meta);
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
        if ($this->ok) {
            return $onSuccess($this->value, $this->meta);
        }

        $error = $this->error;

        if ($error instanceof Throwable) {
            foreach ($exceptionHandlers as $class => $handler) {
                if ($error instanceof $class) {
                    return $handler($error, $this->meta);
                }
            }
        }

        return $onUnhandled($error, $this->meta);
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
        if ($this->ok) {
            return $this->value;
        }
        $err = $this->error;
        if ($err instanceof Throwable) {
            throw $err;
        }
        throw new \RuntimeException(is_string($err) ? $err : 'Result failed');
    }

    /**
     * Provide a default when failed.
     *
     * @param  TSuccess  $default
     * @return TSuccess
     */
    public function unwrapOr(mixed $default): mixed
    {
        return $this->ok ? $this->value : $default;
    }

    /**
     * Unwrap success value or compute default from error lazily.
     *
     * @param  callable(TFailure, array<string,mixed>): TSuccess  $fn
     * @return TSuccess
     */
    public function unwrapOrElse(callable $fn): mixed
    {
        return $this->ok ? $this->value : $fn($this->error, $this->meta);
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
        if ($this->ok) {
            return $this->value;
        }

        throw $exceptionFactory($this->error, $this->meta);
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
        if ($this->ok) {
            return $this;
        }

        $err = $this->error;
        if ($err instanceof Throwable) {
            throw $err;
        }

        throw new \RuntimeException(self::stringifyError($err));
    }

    // =========================================================================
    // Output Transformers
    // =========================================================================

    /**
     * Convert the result to JSON.
     *
     * @param  int  $options  JSON encoding options
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    /**
     * Convert the result to XML.
     */
    public function toXml(string $rootElement = 'result'): string
    {
        $xml = new \SimpleXMLElement("<$rootElement/>");
        $this->arrayToXml($this->toArray(), $xml);

        return (string) $xml->asXML();
    }

    /**
     * Convert the result to an HTTP response (Laravel-compatible if available).
     */
    public function toResponse(): mixed
    {
        $payload = $this->toArray();
        $status = $this->ok ? 200 : 400;

        if (function_exists('response')) {
            return response()->json($payload, $status);
        }

        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload),
        ];
    }

    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            $key = is_numeric($key) ? "item$key" : $key;
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }
    }

    // =========================================================================
    // Internal Pipeline Execution
    // =========================================================================

    /**
     * Internal: normalize Action|callable|array into a folded Result.
     *
     * @param  callable|object|array<callable|object>  $next
     * @param  array<string,mixed>  $meta
     * @return Result<mixed, mixed>
     */
    private function runChain(callable|object|array $next, mixed $input, array $meta): self
    {
        // Allow callable arrays like [$service, 'handle'] to be treated as a single step
        $steps = (! is_array($next) || is_callable($next)) ? [$next] : $next;

        $acc = $this;
        $current = $input;

        foreach ($steps as $step) {
            try {
                $out = $this->invokeStep($step, $current, $meta);
            } catch (Throwable $e) {
                return self::fail($e, array_merge($meta, ['failed_step' => $this->stepName($step)]));
            }

            if ($out instanceof self) {
                $acc = $out;
                $meta = $acc->meta(); // Propagate updated meta to subsequent steps
                if ($acc->isFail()) {
                    return $acc;
                }
                $current = $acc->value();
            } else {
                $acc = self::ok($out, $meta);
                $current = $out;
            }
        }

        return $acc;
    }

    /**
     * Best effort human friendly name for error context.
     */
    private function stepName(callable|object $step): string
    {
        if (is_object($step)) {
            return $step::class;
        }
        if (is_array($step) && isset($step[0], $step[1])) {
            return (is_object($step[0]) ? $step[0]::class : (string) $step[0]).'::'.(string) $step[1];
        }

        return 'closure';
    }

    /**
     * Clone the result with new metadata.
     *
     * @param  array<string,mixed>  $meta
     * @return Result<TSuccess, TFailure>
     */
    private function withMeta(array $meta): self
    {
        return new self($this->ok, $this->value, $this->error, $meta);
    }

    private static function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private static function stringSlice(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, $start, $length) : substr($value, $start, $length);
    }

    private static function stringifyError(mixed $error): string
    {
        if (is_string($error)) {
            return $error;
        }

        try {
            return json_encode($error, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return var_export($error, true);
        }
    }

    /**
     * Invoke a single pipeline step.
     *
     * @param  array<string,mixed>  $meta
     *
     * @throws \InvalidArgumentException
     */
    private function invokeStep(callable|object $step, mixed $arg, array $meta): mixed
    {
        if (is_callable($step)) {
            return $step($arg, $meta);
        }

        if (method_exists($step, 'handle')) {
            return $step->handle($arg, $meta);
        }

        if (method_exists($step, 'execute')) {
            return $step->execute($arg, $meta);
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Step of type %s is not callable and has no handle() or execute() method.',
                $step::class
            )
        );
    }
}
