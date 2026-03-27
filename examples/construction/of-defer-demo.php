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

echo "--- Result::of() and Result::defer() Example ---\n";

printHeader('1) Result::of() wraps a successful throwing callback');
$ofSuccess = Result::of(fn () => strtoupper('result flow'));
printResult('Result', $ofSuccess);

printHeader('2) Result::of() converts thrown exceptions to failure');
$ofFailure = Result::of(function () {
    throw new RuntimeException('boom from Result::of');
});
printResult('Result', $ofFailure);

printHeader('3) Result::defer() accepts a plain value');
$deferValue = Result::defer(fn () => ['mode' => 'defer', 'status' => 'ok']);
printResult('Result', $deferValue);

printHeader('4) Result::defer() preserves returned Result::ok() instances');
$deferOk = Result::defer(fn () => Result::ok('nested success', ['source' => 'inner-ok']));
printResult('Result', $deferOk);

printHeader('5) Result::defer() preserves returned Result::fail() instances');
$deferFail = Result::defer(fn () => Result::fail('nested failure', ['source' => 'inner-fail']));
printResult('Result', $deferFail);

printHeader('6) Result::defer() converts thrown exceptions to failure');
$deferThrown = Result::defer(function () {
    throw new RuntimeException('boom from Result::defer');
});
printResult('Result', $deferThrown);
