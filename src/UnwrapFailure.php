<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow;

use RuntimeException;

/** A failed Result was unwrapped with a non-Throwable failure value. */
final class UnwrapFailure extends RuntimeException
{
    public function __construct(public readonly mixed $failure)
    {
        parent::__construct('Cannot unwrap a failed Result');
    }
}
