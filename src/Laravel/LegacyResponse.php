<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Laravel;

use Maxiviper117\ResultFlow\Result;

/** Explicit opt-in to the v1 HTTP shape and status codes, including metadata. */
final class LegacyResponse
{
    /** @param Result<mixed, mixed> $result */
    public static function fromResult(Result $result): mixed
    {
        return ResultResponse::toResponse($result);
    }
}
