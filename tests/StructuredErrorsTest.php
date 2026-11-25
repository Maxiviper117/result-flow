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
