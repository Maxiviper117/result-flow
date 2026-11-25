<?php

declare(strict_types=1);

use ResultFlowExamples\OrderEndpoint;

require_once __DIR__.'/../examples/v2/OrderEndpoint.php';

it('preserves the legacy order endpoint success response without exposing diagnostics', function () {
    $response = new OrderEndpoint()->handle(' Ada ', 'private-trace');
    expect($response)->toBe([
        'status' => 200,
        'headers' => ['Content-Type' => 'application/json'],
        'body' => '{"ok":true,"value":{"id":42,"name":"Ada"},"error":null,"meta":[]}',
    ])->and($response['body'])->not->toContain('private-trace');
});

it('preserves the legacy order endpoint failure response without exposing diagnostics', function () {
    $response = new OrderEndpoint()->handle(' ', 'private-trace');
    expect($response)->toBe([
        'status' => 400,
        'headers' => ['Content-Type' => 'application/json'],
        'body' => '{"ok":false,"value":null,"error":"Name is required","meta":[]}',
    ])->and($response['body'])->not->toContain('private-trace');
});
