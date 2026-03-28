<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Errors;

use JsonSerializable;
use LogicException;

/**
 * Base tagged/domain error that is throwable and serializable for APIs.
 *
 * This class is intentionally extendable so projects can create domain-specific
 * error classes that carry a stable code/message/payload shape.
 *
 * The domain-level string code is exposed via `code()`. Since PHP exceptions
 * only support integer native codes, inherited `getCode()` is not used as the
 * authoritative domain code and may remain `0`.
 *
 * @phpstan-consistent-constructor
 */
class DataTaggedError extends \RuntimeException implements JsonSerializable, ResultError
{
    /**
     * Optional subclass constant used by `from(...)` for ergonomic construction.
     */
    public const CODE = '';

    private mixed $payload;

    private ?Cause $causeObj;

    public function __construct(string $code, string $message, mixed $payload = null, ?Cause $cause = null)
    {
        parent::__construct($message);
        $this->payload = $payload;
        $this->causeObj = $cause;
        $this->reflectionCode = $code;
    }

    /**
     * Construct a named error from the subclass-defined `CODE` constant.
     */
    public static function from(string $message, mixed $payload = null, ?Cause $cause = null): static
    {
        return new static(static::definedCode(), $message, $payload, $cause);
    }

    private string $reflectionCode = '';

    public function code(): string
    {
        return $this->reflectionCode;
    }

    public function message(): string
    {
        return $this->getMessage();
    }

    public function payload(): mixed
    {
        return $this->payload;
    }

    public function cause(): ?Cause
    {
        return $this->causeObj;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = [
            'code' => $this->code(),
            'message' => $this->message(),
        ];

        if ($this->payload !== null) {
            $out['payload'] = $this->payload;
        }

        if ($this->causeObj !== null) {
            $out['cause'] = $this->causeObj->toArray();
        }

        return $out;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->message();
    }

    protected static function definedCode(): string
    {
        $code = static::CODE;

        if (! is_string($code) || $code === '') {
            throw new LogicException(sprintf(
                '%s must define a non-empty CODE constant or be instantiated with an explicit code.',
                static::class
            ));
        }

        return $code;
    }
}
