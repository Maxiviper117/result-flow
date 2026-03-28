<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Errors;

/**
 * Lightweight nested cause value object for Result errors.
 */
final class Cause
{
    /**
     * @param  array<string,mixed>  $metadata
     * @param  array<int, Cause>  $causes
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
