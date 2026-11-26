<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Output\DebugOutput;
use Maxiviper117\ResultFlow\Output\Serialization;
use Maxiviper117\ResultFlow\Result;

it('serializes results, including active null payloads', function () {
    expect(Serialization::toArray(Result::ok(null, ['trace' => 'a'])))->toBe([
        'ok' => true, 'value' => null, 'error' => null, 'meta' => ['trace' => 'a'],
    ])->and(Serialization::toArray(Result::fail(null)))->toBe([
        'ok' => false, 'value' => null, 'error' => null, 'meta' => [],
    ]);
});

it('redacts sensitive debug metadata and truncates long strings', function () {
    $output = DebugOutput::toArray(Result::ok(null, ['token' => 'secret', 'description' => str_repeat('x', 201)]));
    expect($output['value_type'])->toBe('null')->and($output['meta']['token'])->toBe('***REDACTED***')
        ->and($output['meta']['description'])->toBe(str_repeat('x', 200).'…');
});

it('reports JSON encoding errors', function () {
    expect(fn () => Serialization::toJson(Result::ok(INF)))->toThrow(JsonException::class);
});
