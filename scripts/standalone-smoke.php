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

$failed = Result::fail(null, ['trace' => 'smoke'])
    ->andThen(fn () => throw new RuntimeException('Failure did not short-circuit'));
if (! $failed->isFail() || $failed->match(fn () => 'wrong', fn ($error) => $error) !== null) {
    throw new RuntimeException('Standalone failure branch failed');
}

$recovered = $failed->orElse(fn () => Result::ok('fallback', ['trace' => null]));
if ($recovered->unwrap() !== 'fallback' || $recovered->meta() !== ['trace' => null]) {
    throw new RuntimeException('Standalone recovery or metadata merge failed');
}

$expected = new LogicException('unexpected');
try {
    Result::attempt(fn () => throw $expected, catch: RuntimeException::class);
    throw new RuntimeException('Unexpected exception was captured');
} catch (LogicException $actual) {
    if ($actual !== $expected) {
        throw new RuntimeException('Exception identity was not preserved');
    }
}

echo "Standalone Result flow passed.\n";
