<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$result = Result::flow(fn (): \Generator => yield Result::ok(1));

if ($result->isOk()) {
    $value = $result->value();
}
