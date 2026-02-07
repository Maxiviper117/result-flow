<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

/**
 * Print a section heading for the console demo.
 */
function printHeader(string $title): void
{
    echo "\n=== {$title} ===\n";
}

/**
 * Print a value as pretty JSON so Result shapes are easy to inspect.
 */
function printJson(string $label, mixed $value): void
{
    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "{$label}: {$json}\n";
}

// Demo 1: mapItems keeps one Result per input item (same keys).
printHeader('mapItems (per-item Result objects)');

$items = [
    'row-1' => ['email' => 'good@example.com'],
    'row-2' => ['email' => 'bad-email'],
    'row-3' => ['email' => 'also-good@example.com'],
];

$mapped = Result::mapItems($items, function (array $row, string $key): Result {
    // Each callback invocation can independently succeed or fail.
    if (! str_contains($row['email'], '@')) {
        return Result::fail("Invalid email at {$key}", ['row' => $key]);
    }

    return Result::ok(strtolower($row['email']), ['row' => $key]);
});

printf("%s\n", $mapped['row-1']->isOk() ? 'OK' : 'FAIL');

// // Show each item result to demonstrate per-item branch states.
// foreach ($mapped as $key => $result) {
//     printJson($key, $result->toArray());
// }

// // Demo 2: mapAll stops at the first failure (fail-fast batch flow).
// printHeader('mapAll (fail-fast)');

// $mapAll = Result::mapAll($items, function (array $row, string $key): Result {
//     if (! str_contains($row['email'], '@')) {
//         return Result::fail("Invalid email at {$key}", ['failed_key' => $key]);
//     }

//     return Result::ok(strtoupper($row['email']), ['processed_key' => $key]);
// });

// // Single aggregate Result: first failure aborts remaining item processing.
// printJson('mapAll result', $mapAll->toArray());

// // Demo 3: mapCollectErrors evaluates all items and returns all failures.
// printHeader('mapCollectErrors (collect all failures)');

// $numbers = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];

// $collect = Result::mapCollectErrors($numbers, function (int $number, string $key): Result {
//     // Here odd numbers fail, even numbers succeed.
//     if ($number % 2 === 1) {
//         return Result::fail("Odd number at {$key}", ['key' => $key]);
//     }

//     return Result::ok($number * 100, ['key' => $key]);
// });

// // Aggregate Result includes every keyed failure when any item fails.
// printJson('mapCollectErrors result', $collect->toArray());
