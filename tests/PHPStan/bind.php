<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

/** @param Result<int, never> $result */
function acceptsBoundFlow(Result $result): void {}

$result = Result::flow(function (): Generator {
    return yield from Result::bind(Result::ok(42));
});

acceptsBoundFlow($result);
