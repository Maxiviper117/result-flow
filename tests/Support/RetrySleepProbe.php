<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow;

require dirname(__DIR__, 2).'/vendor/autoload.php';

function sleep(int $seconds): int
{
    echo "seconds:$seconds\n";

    return 0;
}

function usleep(int $microseconds): void
{
    echo "microseconds:$microseconds\n";
}

$attempts = 0;
Retry::config()->maxAttempts(2)->delay(1_500)->attempt(function () use (&$attempts): Result {
    return ++$attempts === 1 ? Result::fail('temporary') : Result::ok('done');
});
