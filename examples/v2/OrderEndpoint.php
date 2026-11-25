<?php

declare(strict_types=1);

namespace ResultFlowExamples;

use Maxiviper117\ResultFlow\Result;

/** An explicit HTTP boundary that excludes diagnostic metadata. */
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

        return $result->match(
            onSuccess: fn (array $order): array => [
                'status' => 200,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['order' => $order], JSON_THROW_ON_ERROR),
            ],
            onFailure: fn (string $error): array => [
                'status' => 400,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['error' => $error], JSON_THROW_ON_ERROR),
            ],
        );
    }
}
