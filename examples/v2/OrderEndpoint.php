<?php

declare(strict_types=1);

namespace ResultFlowExamples;

use Maxiviper117\ResultFlow\Laravel\LegacyResponse;
use Maxiviper117\ResultFlow\Result;

/** A representative legacy endpoint returning the v1 200/400 wire contract. */
final class OrderEndpoint
{
    /** @return array{status: int, headers: array<string, string>, body: string} */
    public function handle(string $name, string $traceId): array
    {
        $result = Result::ok($name, ['trace_id' => $traceId])
            ->map(fn (string $value): string => trim($value))
            ->andThen(fn (string $value): Result => $value === ''
                ? Result::fail('Name is required')
                : Result::ok($value))
            ->andThen(fn (string $value): Result => Result::ok(['id' => 42, 'name' => $value]));

        // The original endpoint had an empty metadata field in its public response.
        // Keep diagnostics inside the application while preserving that contract.
        /** @var array{status: int, headers: array<string, string>, body: string} */
        return LegacyResponse::fromResult($result->withMeta([]));
    }
}
