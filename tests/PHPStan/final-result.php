<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

/** @param Result<int, string> $result */
function acceptsFinalResult(Result $result): void {}

$result = Result::flow(function (): Generator {
    yield Result::fail('step failed');

    return Result::ok(42);
});

acceptsFinalResult($result);
