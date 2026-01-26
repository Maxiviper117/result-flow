<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support;

use Maxiviper117\ResultFlow\Result;
use Throwable;

/**
 * @internal
 */
final class ResultRetry
{
    private int $maxAttempts = 1;

    private int $delayMs = 0;

    private bool $exponential = false;

    private int $jitterMs = 0;

    /** @var callable(mixed, int): bool */
    private $predicate;

    /** @var callable(int, mixed, int): void */
    private $onRetry;

    private function __construct()
    {
        $this->predicate = fn () => true;
        $this->onRetry = fn () => null;
    }

    public static function config(): self
    {
        return new self;
    }

    public function maxAttempts(int $times): self
    {
        $this->maxAttempts = max(1, $times);

        return $this;
    }

    public function delay(int $ms): self
    {
        $this->delayMs = max(0, $ms);

        return $this;
    }

    public function exponential(bool $enabled = true): self
    {
        $this->exponential = $enabled;

        return $this;
    }

    public function jitter(int $ms): self
    {
        $this->jitterMs = max(0, $ms);

        return $this;
    }

    public function when(callable $predicate): self
    {
        $this->predicate = $predicate;

        return $this;
    }

    public function onRetry(callable $callback): self
    {
        $this->onRetry = $callback;

        return $this;
    }

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
                        return $value;
                    }
                    $lastError = $value->error();
                    $lastResult = $value;
                } else {
                    return Result::ok($value);
                }
            } catch (Throwable $e) {
                $lastError = $e;
                $lastResult = Result::fail($e);
            }

            if ($attempts >= $this->maxAttempts) {
                return $lastResult;
            }

            if (! ($this->predicate)($lastError, $attempts)) {
                return $lastResult;
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
}