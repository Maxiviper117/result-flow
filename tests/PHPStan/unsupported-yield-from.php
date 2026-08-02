<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

Result::flow(function (): \Generator {
    yield from (function (): \Generator {
        yield Result::ok(1);
    })();

    return null;
});
