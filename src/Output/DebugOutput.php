<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Output;

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Output\Debug;

/** Diagnostic output for logs and debugging; do not expose it to clients. */
final class DebugOutput
{
    /**
     * @param  Result<mixed, mixed>  $result
     * @param  callable(mixed): mixed|null  $sanitizer
     * @return array{ok: bool, value_type: string|null, error_type: string|null, error_code: string|null, error_message: mixed, error_cause: mixed, meta: mixed}
     */
    public static function toArray(Result $result, ?callable $sanitizer = null): array
    {
        return Debug::toDebugArray($result, $sanitizer);
    }
}
