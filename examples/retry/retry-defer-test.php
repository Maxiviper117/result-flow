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

function printResult(string $label, Result $result, int $attempts): void
{
    echo $label.":\n";
    echo json_encode(
        [
            'attempts' => $attempts,
            'ok' => $result->isOk(),
            'value' => $result->value(),
            'error' => normalizeError($result->error()),
            'meta' => $result->meta(),
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    )."\n";
}

echo "--- Result::retryDefer() Example ---\n";

$attempts = 0;
printHeader('1) Throws twice, then returns plain value');
$successAfterRetries = Result::retryDefer(3, function () use (&$attempts) {
    $attempts++;

    if ($attempts < 3) {
        throw new RuntimeException("Transient failure at attempt {$attempts}");
    }

    return ['status' => 'ok', 'attempt' => $attempts];
}, delay: 10, exponential: true);
printResult('Result', $successAfterRetries, $attempts);

$resultAttempts = 0;
printHeader('2) Returns Result::fail once, then Result::ok');
$resultRetry = Result::retryDefer(3, function () use (&$resultAttempts) {
    $resultAttempts++;

    if ($resultAttempts < 2) {
        return Result::fail('Temporary Result failure');
    }

    return Result::ok('Recovered from Result failure');
});
printResult('Result', $resultRetry, $resultAttempts);

$alwaysFailAttempts = 0;
printHeader('3) Exhausts retries and returns terminal failure');
$alwaysFail = Result::retryDefer(2, function () use (&$alwaysFailAttempts) {
    $alwaysFailAttempts++;

    throw new RuntimeException('Still failing');
});
printResult('Result', $alwaysFail, $alwaysFailAttempts);
