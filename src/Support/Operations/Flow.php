<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Operations;

use Generator;
use LogicException;
use Maxiviper117\ResultFlow\Result;
use Throwable;

/**
 * Runs synchronous generator-based Result workflows.
 *
 * @internal
 */
final class Flow
{
    /**
     * @param  callable(): mixed  $workflow
     * @return Result<covariant mixed, covariant mixed>
     */
    public static function run(callable $workflow): Result
    {
        $generator = $workflow();

        if (! $generator instanceof Generator) {
            throw new LogicException(
                'Result::flow() callback must return a Generator.',
            );
        }

        /** @var array<string, mixed> $metadata */
        $metadata = [];
        $failure = null;

        try {
            while ($generator->valid()) {
                $yielded = $generator->current();

                if (! $yielded instanceof Result) {
                    throw new LogicException(
                        sprintf(
                            'Result::flow() expected a Result, %s yielded.',
                            get_debug_type($yielded),
                        ),
                    );
                }

                $metadata = array_replace($metadata, $yielded->meta());

                if ($yielded->isFail()) {
                    $failure = $yielded;
                    break;
                }

                $generator->send($yielded->value());
            }

            if ($failure instanceof Result) {
                unset($generator);

                return Result::fail($failure->error(), $metadata);
            }

            $returned = $generator->getReturn();

            if ($returned instanceof Result) {
                if ($metadata === []) {
                    return $returned;
                }

                return $returned->isOk()
                    ? Result::ok(
                        $returned->value(),
                        array_replace($metadata, $returned->meta()),
                    )
                    : Result::fail(
                        $returned->error(),
                        array_replace($metadata, $returned->meta()),
                    );
            }

            return Result::ok($returned, $metadata);
        } catch (Throwable $exception) {
            unset($generator);

            throw $exception;
        }
    }
}
