<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\ResultBatch;

$result = ResultBatch::mapAllErrors(['name' => 'Ada', 'email' => ''],
    fn (string $value): Result => $value === '' ? Result::fail('Required') : Result::ok($value));

echo json_encode($result->match(fn ($values) => $values, fn ($errors) => $errors), JSON_THROW_ON_ERROR)."\n";
