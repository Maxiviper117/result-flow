<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Errors;

/**
 * Interface representing a structured, serializable error payload for Results.
 */
interface ResultError
{
    public function code(): string;

    public function message(): string;

    /**
     * Optional payload useful for HTTP APIs / debugging.
     */
    public function payload(): mixed;

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array;

    /**
     * Optional cause chain attached to this error.
     */
    public function cause(): ?Cause;
}
