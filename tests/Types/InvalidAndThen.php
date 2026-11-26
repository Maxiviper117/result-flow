<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

Result::ok(1)->andThen(fn (int $value): int => $value + 1);
