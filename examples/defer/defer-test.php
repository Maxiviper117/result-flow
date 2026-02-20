<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

function printHeader(string $title): void
{
    echo "\n=== {$title} ===\n";
}

function normalizeError(mixed $error): mixed
{
    if ($error instanceof Throwable) {
        return [
            'type' => $error::class,
            'message' => $error->getMessage(),
        ];
    }

    return $error;
}

function printResult(string $label, Result $result): void
{
    echo $label.":\n";
    echo json_encode(
        [
            'ok' => $result->isOk(),
            'value' => $result->value(),
            'error' => normalizeError($result->error()),
            'meta' => $result->meta(),
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    )."\n";
}

echo "--- Result::defer() Example ---\n";

printHeader('1) Callback returns plain value');
$plain = Result::defer(fn () => 42);
printResult('Result', $plain);

printHeader('2) Callback returns Result::ok with metadata');
$okResult = Result::defer(fn () => Result::ok('from inner result', ['source' => 'inner']));
printResult('Result', $okResult);

printHeader('3) Callback returns Result::fail with metadata');
$failResult = Result::defer(fn () => Result::fail('inner fail', ['source' => 'inner-fail']));
printResult('Result', $failResult);

printHeader('4) Callback throws Throwable');
$thrown = Result::defer(function () {
    throw new RuntimeException('boom from defer');
});
printResult('Result', $thrown);

printHeader('5) Chaining after defer');
$chain = Result::defer(fn () => ['id' => 123, 'name' => 'Alice'])
    ->then(fn (array $user) => Result::ok(array_merge($user, ['active' => true])))
    ->ensure(fn (array $user, array $meta) => $user['active'] === true, 'User must be active')
    ->mapMeta(fn (array $meta) => array_merge($meta, ['scenario' => 'chained-defer']));
printResult('Result', $chain);
