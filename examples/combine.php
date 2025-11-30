<?php

declare(strict_types=1);

/**
 * Demonstrates combine() vs combineAll() for aggregating multiple Results.
 *
 * Run with:
 * php examples/combine.php
 */

require __DIR__.'/../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

function validateField(string $name, mixed $value): Result
{
    if ($value === null || $value === '') {
        return Result::fail("{$name} is required", ['field' => $name]);
    }

    if ($name === 'age' && $value < 18) {
        return Result::fail("{$name} must be 18+", ['field' => $name, 'value' => $value]);
    }

    return Result::ok([$name => $value]);
}

$inputs = [
    'happy' => ['name' => 'Ada', 'email' => 'ada@example.com', 'age' => 30],
    'missing-email' => ['name' => 'Grace', 'email' => '', 'age' => 40],
    'underage' => ['name' => 'Eve', 'email' => 'eve@example.com', 'age' => 16],
];

foreach ($inputs as $label => $data) {
    echo PHP_EOL.'['.$label.']'.PHP_EOL;

    $results = [
        validateField('name', $data['name'] ?? null),
        validateField('email', $data['email'] ?? null),
        validateField('age', $data['age'] ?? null),
    ];

    $shortCircuit = Result::combine($results);
    $allErrors = Result::combineAll($results);

    echo 'combine (first error wins): '.json_encode($shortCircuit->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT).PHP_EOL;
    echo 'combineAll (collect errors): '.json_encode($allErrors->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT).PHP_EOL;
}
