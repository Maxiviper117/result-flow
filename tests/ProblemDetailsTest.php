<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Output\ProblemDetails;
use Maxiviper117\ResultFlow\Result;

it('maps an error explicitly to a problem response without leaking diagnostics', function () {
    $result = Result::fail('internal error', ['token' => 'secret']);
    $response = $result->match(
        fn () => throw new LogicException('unexpected success'),
        fn () => ProblemDetails::response(422, 'Invalid input', detail: 'Check your fields', extensions: ['errors' => ['name' => ['required']]]),
    );
    expect($response['status'])->toBe(422)
        ->and($response['headers']['Content-Type'])->toBe('application/problem+json')
        ->and(json_decode($response['body'], true, flags: JSON_THROW_ON_ERROR))->toBe([
            'type' => 'about:blank', 'title' => 'Invalid input', 'status' => 422,
            'detail' => 'Check your fields', 'errors' => ['name' => ['required']],
        ])
        ->and($response['body'])->not->toContain('secret')->not->toContain('internal error');
});

it('rejects invalid statuses, reserved fields and JSON encoding failures', function () {
    expect(fn () => ProblemDetails::response(99, 'Bad'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => ProblemDetails::response(422, 'Bad', extensions: ['status' => 200]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => ProblemDetails::response(422, 'Bad', extensions: ['value' => INF]))->toThrow(JsonException::class);
});
