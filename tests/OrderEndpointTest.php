<?php

declare(strict_types=1);

use ResultFlowExamples\OrderEndpoint;

require_once __DIR__.'/../examples/v2/OrderEndpoint.php';

it('maps the order endpoint success response without exposing diagnostics', function () {
    $response = new OrderEndpoint()->handle(' Ada ', 'private-trace');
    expect($response)->toBe([
        'status' => 200,
        'headers' => ['Content-Type' => 'application/json'],
        'body' => '{"order":{"id":42,"name":"Ada"}}',
    ])->and($response['body'])->not->toContain('private-trace');
});

it('maps the order endpoint failure response without exposing diagnostics', function () {
    $response = new OrderEndpoint()->handle(' ', 'private-trace');
    expect($response)->toBe([
        'status' => 400,
        'headers' => ['Content-Type' => 'application/json'],
        'body' => '{"error":"Name is required"}',
    ])->and($response['body'])->not->toContain('private-trace');
});
