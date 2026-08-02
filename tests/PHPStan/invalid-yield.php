<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

Result::flow(function (): \Generator {
    yield 'not a Result';

    return null;
});
