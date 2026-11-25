<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Laravel\LegacyResponse;
use Maxiviper117\ResultFlow\Output\DebugOutput;
use Maxiviper117\ResultFlow\Output\LegacyOutput;
use Maxiviper117\ResultFlow\Result;

it('preserves the legacy serialization shape, including active null payloads', function () {
    expect(LegacyOutput::toArray(Result::ok(null, ['trace' => 'a'])))->toBe([
        'ok' => true, 'value' => null, 'error' => null, 'meta' => ['trace' => 'a'],
    ])->and(LegacyOutput::toArray(Result::fail(null)))->toBe([
        'ok' => false, 'value' => null, 'error' => null, 'meta' => [],
    ]);
});

it('preserves the legacy HTTP fallback shape and status', function () {
    $response = LegacyResponse::fromResult(Result::fail('bad', ['trace' => 'a']));
    expect($response['status'])->toBe(400)
        ->and(json_decode($response['body'], true, flags: JSON_THROW_ON_ERROR))->toBe([
            'ok' => false, 'value' => null, 'error' => 'bad', 'meta' => ['trace' => 'a'],
        ]);
});

it('redacts sensitive debug metadata and truncates long strings', function () {
    $output = DebugOutput::toArray(Result::ok(null, ['token' => 'secret', 'description' => str_repeat('x', 201)]));
    expect($output['value_type'])->toBe('null')->and($output['meta']['token'])->toBe('***REDACTED***')
        ->and($output['meta']['description'])->toBe(str_repeat('x', 200).'…');
});

it('reports legacy json encoding errors', function () {
    expect(fn () => LegacyOutput::toJson(Result::ok(INF)))->toThrow(JsonException::class);
});
