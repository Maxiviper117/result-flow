<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

/** @param Result<42, string> $result */
function acceptsFailureUnion(Result $result): void {}

$result = Result::flow(function (): Generator {
    yield Result::fail('step failed');

    return 42;
});

acceptsFailureUnion($result);
