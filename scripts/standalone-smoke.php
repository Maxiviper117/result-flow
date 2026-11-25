<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

$result = Result::ok(' 42 ', ['trace' => 'smoke'])
    ->map(fn (string $value): int => (int) trim($value))
    ->andThen(fn (int $value): Result => $value > 0 ? Result::ok($value) : Result::fail('invalid'));

if ($result->match(fn ($value) => $value, fn ($error) => null) !== 42 || $result->meta() !== ['trace' => 'smoke']) {
    throw new RuntimeException('Standalone Result flow failed');
}

echo "Standalone Result flow passed.\n";
