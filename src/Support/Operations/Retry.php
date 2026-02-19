<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Operations;

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Traits\MetaOps;
use Throwable;

/**
 * Fluent retry configuration for Result operations.
 *
 * @internal
 */
final class Retry
{
    private int $maxAttempts = 1;

    private int $delayMs = 0;

    private bool $exponential = false;

    private int $jitterMs = 0;

    private bool $attachAttemptMeta = false;

    /** @var callable(mixed, int): bool */
    private $predicate;

    /** @var callable(int, mixed, int): void */
    private $onRetry;

    /**
     * Initialize defaults for retry behavior.
     */
    private function __construct()
    {
        $this->predicate = fn () => true;
        $this->onRetry = fn () => null;
    }

    /**
     * Create a new retry configuration instance.
     */
    public static function config(): self
    {
        return new self;
    }

    /**
     * Set the maximum number of attempts (minimum 1).
     */
    public function maxAttempts(int $times): self
    {
        $this->maxAttempts = max(1, $times);

        return $this;
    }

    /**
     * Set the base delay between attempts in milliseconds.
     */
    public function delay(int $ms): self
    {
        $this->delayMs = max(0, $ms);

        return $this;
    }

    /**
     * Enable or disable exponential backoff for delays.
     */
    public function exponential(bool $enabled = true): self
    {
        $this->exponential = $enabled;

        return $this;
    }

    /**
     * Add random jitter up to the given milliseconds.
     */
    public function jitter(int $ms): self
    {
        $this->jitterMs = max(0, $ms);

        return $this;
    }

    /**
     * Enable or disable attaching retry metadata to the Result meta.
     */
    public function attachAttemptMeta(bool $enable = true): self
    {
        $this->attachAttemptMeta = $enable;

        return $this;
    }

    /**
     * Set a predicate that decides whether to retry after a failure.
     *
     * @param  callable(mixed, int): bool  $predicate
     */
    public function when(callable $predicate): self
    {
        $this->predicate = $predicate;

        return $this;
    }

    /**
     * Register a callback invoked before each retry.
     *
     * @param  callable(int, mixed, int): void  $callback
     */
    public function onRetry(callable $callback): self
    {
        $this->onRetry = $callback;

        return $this;
    }

    /**
     * Execute the operation with retry behavior.
     *
     * @param  callable(): (Result<mixed, mixed>|mixed)  $fn
     * @return Result<mixed, mixed>
     */
    public function attempt(callable $fn): Result
    {
        $attempts = 0;
        $lastError = null;
        $lastResult = null;

        while (true) {
            $attempts++;

            try {
                $value = $fn();

                if ($value instanceof Result) {
                    if ($value->isOk()) {
                        return $this->attachMetaIfNeeded($value, $attempts);
                    }
                    $lastError = $value->error();
                    $lastResult = $value;
                } else {
                    return $this->attachMetaIfNeeded(Result::ok($value), $attempts);
                }
            } catch (Throwable $e) {
                $lastError = $e;
                $lastResult = Result::fail($e);
            }

            if ($attempts >= $this->maxAttempts) {
                return $this->attachMetaIfNeeded($lastResult, $attempts);
            }

            if (! ($this->predicate)($lastError, $attempts)) {
                return $this->attachMetaIfNeeded($lastResult, $attempts);
            }

            $wait = $this->delayMs;
            if ($this->exponential) {
                $wait = $this->delayMs * (2 ** ($attempts - 1));
            }

            if ($this->jitterMs > 0) {
                $wait += random_int(0, $this->jitterMs);
            }

            ($this->onRetry)($attempts, $lastError, $wait);

            if ($wait > 0) {
                usleep($wait * 1000);
            }
        }
    }

    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     * @return Result<TSuccess, TFailure>
     */
    private function attachMetaIfNeeded(Result $result, int $attempts): Result
    {
        if (! $this->attachAttemptMeta) {
            return $result;
        }

        return MetaOps::mergeMeta(
            $result,
            ['retry' => ['attempts' => $attempts]],
        );
    }
}
