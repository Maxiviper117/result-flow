<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Output;

use InvalidArgumentException;

/** Explicit, framework-independent RFC 9457 problem response builder. */
final class ProblemDetails
{
    /**
     * Only supplied fields are exposed. The caller chooses how domain errors map here.
     *
     * @param  array<string, mixed>  $extensions
     * @return array{status: int, headers: array{Content-Type: string}, body: string}
     */
    public static function response(
        int $status,
        string $title,
        string $type = 'about:blank',
        ?string $detail = null,
        ?string $instance = null,
        array $extensions = [],
    ): array {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException('HTTP status must be between 100 and 599');
        }
        if ($status < 200 || in_array($status, [204, 205, 304], true)) {
            throw new InvalidArgumentException('HTTP status does not permit a response body');
        }
        foreach (['type', 'title', 'status', 'detail', 'instance'] as $reserved) {
            if (array_key_exists($reserved, $extensions)) {
                throw new InvalidArgumentException('Problem extensions cannot replace standard fields');
            }
        }

        $body = ['type' => $type, 'title' => $title, 'status' => $status];
        if ($detail !== null) {
            $body['detail'] = $detail;
        }
        if ($instance !== null) {
            $body['instance'] = $instance;
        }
        $body = array_merge($body, $extensions);

        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/problem+json'],
            'body' => json_encode($body, JSON_THROW_ON_ERROR),
        ];
    }
}
