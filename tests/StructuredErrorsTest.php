<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Output\DebugOutput;
use Maxiviper117\ResultFlow\Output\Serialization;
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\Cause;
use Maxiviper117\ResultFlow\Support\Errors\DataTaggedError;

it('preserves structured error and nested cause serialization', function () {
    $cause = new Cause('E_PERSIST', 'Persist failed', [], [new Cause('E_DB', 'DB failed')]);
    $error = new DataTaggedError('E_MISSING', 'Not found', ['id' => 42], $cause);
    $result = Result::fail($error);
    expect(Serialization::toArray($result)['error'])->toBe($error->toArray())
        ->and(json_decode(Serialization::toJson($result), true)['error']['cause']['causes'][0]['code'])->toBe('E_DB')
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

it('bounds cause expansion before invoking a custom sanitizer', function () {
    $cause = new Cause('LEAF', 'leaf');
    for ($i = 0; $i < 40; $i++) {
        $cause = new Cause('NODE', 'node', [], [$cause, $cause]);
    }
    $result = Result::fail(new DataTaggedError('FAIL', 'failure', cause: $cause));
    $debug = DebugOutput::toArray($result, fn ($value) => $value);
    $encoded = json_encode($debug, JSON_THROW_ON_ERROR);
    expect($encoded)->toContain('[cause_limit_exceeded]')->and(strlen($encoded))->toBeLessThan(30000);
});

it('limits wide cause lists at the depth boundary', function () {
    $leaf = new Cause('LEAF', 'leaf');
    $cause = new Cause('WIDE', 'wide', [], array_fill(0, 10000, $leaf));
    for ($i = 0; $i < 31; $i++) {
        $cause = new Cause('NODE', 'node', [], [$cause]);
    }
    $debug = DebugOutput::toArray(Result::fail(new DataTaggedError('FAIL', 'failure', cause: $cause)), fn ($value) => $value);
    $encoded = json_encode($debug, JSON_THROW_ON_ERROR);
    expect($encoded)->toContain('[cause_limit_exceeded]')->and(strlen($encoded))->toBeLessThan(5000);
});

it('rejects excessive structured cause expansion at every serialization boundary', function () {
    $cause = new Cause('LEAF', 'leaf');
    for ($i = 0; $i < 20; $i++) {
        $cause = new Cause('NODE', 'node', [], [$cause, $cause]);
    }
    $error = new DataTaggedError('FAIL', 'failure', cause: $cause);
    $result = Result::fail($error);
    expect(fn () => $cause->toArray())->toThrow(LengthException::class)
        ->and(fn () => $error->toArray())->toThrow(LengthException::class)
        ->and(fn () => Serialization::toArray($result))->toThrow(LengthException::class)
        ->and(fn () => Serialization::toJson($result))->toThrow(LengthException::class)
        ->and(fn () => Serialization::toXml($result))->toThrow(LengthException::class);
});

it('rejects deep structured causes while allowing shared causes within the limit', function () {
    $leaf = new Cause('LEAF', 'leaf');
    $shared = new Cause('ROOT', 'root', [], [$leaf, $leaf]);
    expect($shared->toArray()['causes'])->toHaveCount(2);
    $deep = $leaf;
    for ($i = 0; $i < 32; $i++) {
        $deep = new Cause('NODE', 'node', [], [$deep]);
    }
    expect(fn () => $deep->toArray())->toThrow(LengthException::class);
});
