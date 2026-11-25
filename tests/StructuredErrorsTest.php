<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Output\DebugOutput;
use Maxiviper117\ResultFlow\Output\LegacyOutput;
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\Cause;
use Maxiviper117\ResultFlow\Support\Errors\DataTaggedError;

it('preserves structured error and nested cause serialization', function () {
    $cause = new Cause('E_PERSIST', 'Persist failed', [], [new Cause('E_DB', 'DB failed')]);
    $error = new DataTaggedError('E_MISSING', 'Not found', ['id' => 42], $cause);
    $result = Result::fail($error);
    expect(LegacyOutput::toArray($result)['error'])->toBe($error->toArray())
        ->and(json_decode(LegacyOutput::toJson($result), true)['error']['cause']['causes'][0]['code'])->toBe('E_DB')
        ->and(DebugOutput::toArray($result)['error_code'])->toBe('E_MISSING');
    try {
        $result->unwrap();
        test()->fail('Expected exception');
    } catch (DataTaggedError $caught) {
        expect($caught)->toBe($error);
    }
});

it('includes sanitized nested causes in debug output', function () {
    $inner = new Cause('DB', str_repeat('x', 201), ['password' => 'secret']);
    $outer = new Cause('WRITE', 'Write failed', ['token' => 'private'], [$inner]);
    $error = new DataTaggedError('ORDER', 'Could not save', cause: $outer);
    $cause = DebugOutput::toArray(Result::fail($error))['error_cause'];
    expect($cause['code'])->toBe('WRITE')
        ->and($cause['metadata']['token'])->toBe('***REDACTED***')
        ->and($cause['causes'][0]['metadata']['password'])->toBe('***REDACTED***')
        ->and($cause['causes'][0]['message'])->toBe(str_repeat('x', 200).'…');
});

it('preserves an error cause explicitly when normalizing a failure', function () {
    $original = new DataTaggedError('DB', 'Database failed', cause: new Cause('SOCKET', 'Connection lost'));
    $result = Result::fail($original)->mapError(function (DataTaggedError $error): DataTaggedError {
        $previous = $error->cause();

        return new DataTaggedError(
            'ORDER_WRITE',
            'Order could not be saved',
            cause: new Cause($error->code(), $error->message(), [], $previous instanceof Cause ? [$previous] : []),
        );
    });
    $cause = DebugOutput::toArray($result)['error_cause'];
    expect($cause['code'])->toBe('DB')->and($cause['causes'][0]['code'])->toBe('SOCKET');
});
