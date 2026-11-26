<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Errors;

/** @internal */
final class CauseTraversal
{
    private const int MAX_DEPTH = 32;

    private const int MAX_NODES = 256;

    /** @return array<string, mixed> */
    public static function expand(Cause $cause, bool $truncate = false): array
    {
        $remaining = self::MAX_NODES;

        return self::visit($cause, 0, $remaining, $truncate);
    }

    /** @return array<string, mixed> */
    private static function visit(Cause $cause, int $depth, int &$remaining, bool $truncate): array
    {
        $remaining--;
        $out = ['code' => $cause->code(), 'message' => $cause->message(), 'metadata' => $cause->metadata()];
        $children = $cause->causes();
        if ($children !== []) {
            $out['causes'] = [];
            foreach ($children as $child) {
                if ($remaining <= 0 || $depth + 1 >= self::MAX_DEPTH) {
                    if (! $truncate) {
                        throw new \LengthException('Structured cause exceeds the depth or node limit');
                    }
                    $out['causes'][] = '[cause_limit_exceeded]';
                    break;
                }
                $out['causes'][] = self::visit($child, $depth + 1, $remaining, $truncate);
            }
        }

        return $out;
    }
}
