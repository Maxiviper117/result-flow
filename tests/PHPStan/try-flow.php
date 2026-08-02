<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

/** @param Result<42, string|Throwable> $result */
function acceptsTryFlow(Result $result): void {}

$result = Result::tryFlow(function (): Generator {
    yield Result::fail('step failed');

    return 42;
});

acceptsTryFlow($result);
