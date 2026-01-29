<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Laravel;

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\ResultSerialization;

/**
 * Convert Result instances to HTTP responses when Laravel is available.
 *
 * @internal
 */
final class ResultResponse
{
    /**
     * Convert a Result to a JSON HTTP response or fallback array shape.
     *
     * @template TSuccess
     * @template TFailure
     *
     * @param  Result<TSuccess, TFailure>  $result
     */
    public static function toResponse(Result $result): mixed
    {
        $payload = ResultSerialization::toArray($result);
        $status = $result->isOk() ? 200 : 400;

        if (function_exists('response')) {
            $factory = response();
            if (is_object($factory) && method_exists($factory, 'json')) {
                /** @var callable(mixed, int, array<string, string>, int): mixed $json */
                $json = [$factory, 'json'];

                return $json($payload, $status, [], 0);
            }
        }

        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload),
        ];
    }
}
