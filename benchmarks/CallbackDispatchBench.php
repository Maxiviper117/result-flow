<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Benchmarks;

use JsonSerializable;
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\DataTaggedError;

final class CallbackDispatchBench
{
    private Result $successResult;

    private Result $errorResult;

    private object $arrayHandler;

    private object $invokableHandler;

    /**
     * @BeforeMethods({"init"})
     */
    public function benchMatchErrorWithClosure(): void
    {
        $this->errorResult->matchError(
            [BenchError::class => fn (BenchError $e, array $meta): string => $e->code().($meta['request_id'] ?? '')],
            onSuccess: fn (): string => 'ok',
            onUnhandled: fn (): string => 'unhandled',
        );
    }

    /**
     * @BeforeMethods({"init"})
     */
    public function benchMatchErrorWithArrayCallable(): void
    {
        $this->errorResult->matchError(
            [BenchError::class => [$this->arrayHandler, 'handle']],
            onSuccess: fn (): string => 'ok',
            onUnhandled: fn (): string => 'unhandled',
        );
    }

    /**
     * @BeforeMethods({"init"})
     */
    public function benchMatchErrorWithInvokable(): void
    {
        $this->errorResult->matchError(
            [BenchError::class => $this->invokableHandler],
            onSuccess: fn (): string => 'ok',
            onUnhandled: fn (): string => 'unhandled',
        );
    }

    /**
     * @BeforeMethods({"init"})
     */
    public function benchMatchErrorWithStringCallable(): void
    {
        $this->errorResult->matchError(
            [BenchError::class => __NAMESPACE__.'\\benchMatchStringHandler'],
            onSuccess: fn (): string => 'ok',
            onUnhandled: fn (): string => 'unhandled',
        );
    }

    /**
     * @BeforeMethods({"init"})
     */
    public function benchCatchErrorWithArrayCallable(): void
    {
        $this->errorResult->catchError([
            BenchError::class => [$this->arrayHandler, 'recover'],
        ]);
    }

    /**
     * @BeforeMethods({"init"})
     */
    public function benchMapMetaWithArrayCallable(): void
    {
        $this->successResult->mapMeta([$this->arrayHandler, 'mapMeta']);
    }

    /**
     * @BeforeMethods({"init"})
     */
    public function benchMergeMetaWithInvokable(): void
    {
        $this->successResult->mergeMeta($this->invokableHandler);
    }

    /**
     * @BeforeMethods({"init"})
     */
    public function benchTapMetaWithStringCallable(): void
    {
        $this->successResult->tapMeta(__NAMESPACE__.'\\benchTapMetaStringHandler');
    }

    public function init(): void
    {
        $this->successResult = Result::ok(
            ['payload' => 42],
            ['request_id' => 'r-1', 'token' => str_repeat('x', 16)],
        );
        $this->errorResult = Result::fail(new BenchError('E_BENCH', 'bench fail'), ['request_id' => 'r-1']);
        $this->arrayHandler = new class
        {
            public function handle(BenchError $e, array $meta): string
            {
                return $e->code().($meta['request_id'] ?? '');
            }

            public function recover(BenchError $e): string
            {
                return 'recover:'.$e->code();
            }

            public function mapMeta(array $meta, mixed $value = null): array
            {
                return [...$meta, 'value_type' => get_debug_type($value)];
            }
        };
        $this->invokableHandler = new class
        {
            public function __invoke(mixed ...$args): mixed
            {
                if ($args === []) {
                    return [];
                }

                if (is_array($args[0])) {
                    $meta = $args[0];
                    $value = $args[1] ?? null;

                    return ['invokable' => get_debug_type($value)] + $meta;
                }

                if ($args[0] instanceof BenchError) {
                    return 'invoke:'.$args[0]->code();
                }

                return null;
            }
        };
    }
}

final class BenchError extends DataTaggedError implements JsonSerializable
{
    public const CODE = 'E_BENCH';

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}

function benchMatchStringHandler(BenchError $e, array $meta): string
{
    return 'string:'.$e->code().($meta['request_id'] ?? '');
}

function benchTapMetaStringHandler(array $meta, mixed $value = null): void {}
