<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Errors;

/**
 * Lightweight nested cause value object for Result errors.
 * - This is intentionally not an Exception since it's meant to be a simple value object that can be nested and serialized, without the overhead of stack traces or PHP's exception chaining.
 */
final class Cause
{
    /**
     * @param  string|null  $code  - optional domain-level code for the cause (e.g. 'E_DB', 'E_SQL', etc.)
     * @param  string  $message  - human-readable message describing the cause
     * @param  array<string,mixed>  $metadata  - optional structured metadata about the cause (e.g. DB table, constraint, etc.)
     * @param  array<int, Cause>  $causes  - nested causes for multi-level error representation
     */
    public function __construct(
        private ?string $code,
        private string $message,
        private array $metadata = [],
        private array $causes = [],
    ) {}

    public function code(): ?string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    /** @return array<string,mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return array<int, Cause>
     */
    public function causes(): array
    {
        return $this->causes;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = [
            'code' => $this->code,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];

        if (! empty($this->causes)) {
            $causesArr = [];
            foreach ($this->causes as $c) {
                $causesArr[] = $c->toArray();
            }
            $out['causes'] = $causesArr;
        }

        return $out;
    }
}
