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
    $meta = $result->meta();
    if (($meta['bracket.release_exception'] ?? null) instanceof Throwable) {
        /** @var Throwable $releaseException */
        $releaseException = $meta['bracket.release_exception'];
        $meta['bracket.release_exception'] = [
            'type' => $releaseException::class,
            'message' => $releaseException->getMessage(),
        ];
    }

    echo json_encode(
        [
            'ok' => $result->isOk(),
            'value' => $result->value(),
            'error' => normalizeError($result->error()),
            'meta' => $meta,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    )."\n";
}

echo "--- Result::bracket() Example ---\n";

printHeader('1) Acquire, use, release all succeed');
$ok = Result::bracket(
    acquire: fn () => fopen('php://temp', 'r+'),
    use: function ($handle) {
        fwrite($handle, 'hello bracket');
        rewind($handle);

        return fread($handle, 13);
    },
    release: fn ($handle) => fclose($handle)
);
printResult('Result', $ok);

printHeader('2) Use fails, release succeeds');
$useFailReleaseOk = Result::bracket(
    acquire: fn () => 'resource',
    use: fn (string $resource) => Result::fail('use failed', ['phase' => 'use']),
    release: fn (string $resource) => null
);
printResult('Result', $useFailReleaseOk);

printHeader('3) Use fails and release throws');
$useFailReleaseThrows = Result::bracket(
    acquire: fn () => 'resource',
    use: fn (string $resource) => Result::fail('use failed', ['phase' => 'use']),
    release: function (string $resource): void {
        throw new RuntimeException('release failed');
    }
);
printResult('Result', $useFailReleaseThrows);

printHeader('4) Use succeeds but release throws');
$useOkReleaseThrows = Result::bracket(
    acquire: fn () => 'resource',
    use: fn (string $resource) => Result::ok('use ok', ['phase' => 'use']),
    release: function (string $resource): void {
        throw new RuntimeException('release failed after success');
    }
);
printResult('Result', $useOkReleaseThrows);

printHeader('5) Acquire fails (release is not executed)');
$acquireFail = Result::bracket(
    acquire: fn () => Result::fail('could not acquire lock', ['phase' => 'acquire']),
    use: fn (string $resource) => Result::ok('never reached'),
    release: fn (string $resource) => null
);
printResult('Result', $acquireFail);
