<?php

declare(strict_types=1);

/**
 * Standalone example demonstrating Result usage.
 *
 * Run with:
 * php examples/basic.php
 */

require __DIR__.'/../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

function chargePayment(array $order, array $meta): Result
{
    $token = $order['payment_token'] ?? null;

    if ($token !== 'tok_chargeable') {
        return Result::fail('Card declined', $meta + ['payment_token' => $token]);
    }

    return Result::ok(
        $order + ['status' => 'paid'],
        $meta + ['payment_provider' => 'demo-pay'],
    );
}

function checkout(array $order): Result
{
    return Result::ok($order, ['request_id' => $order['id'] ?? 'demo-order'])
        ->ensure(fn($payload) => ! empty($payload['items']), 'Cart is empty')
        ->then('chargePayment')
        ->otherwise(function ($error, array $meta) use ($order) {
            if ($error === 'Card declined') {
                return Result::ok(
                    [
                        'id' => $order['id'] ?? 'demo-order',
                        'status' => 'queued',
                        'reason' => 'Payment will be retried later',
                    ],
                    $meta + ['queued' => true],
                );
            }

            return Result::fail($error, $meta);
        });
}

$examples = [
    'happy-path' => [
        'id' => 'order-1001',
        'items' => ['book', 'pen'],
        'payment_token' => 'tok_chargeable',
    ],
    'payment-declined' => [
        'id' => 'order-1002',
        'items' => ['book'],
        'payment_token' => 'tok_bad_card',
    ],
    'empty-cart' => [
        'id' => 'order-1003',
        'items' => [],
        'payment_token' => 'tok_chargeable',
    ],
];

foreach ($examples as $label => $order) {
    $result = checkout($order);

    echo PHP_EOL.'['.$label.'] '.PHP_EOL;

    echo $result->match(
        onSuccess: fn($payload, array $meta) => 'OK   '.json_encode(
            ['payload' => $payload, 'meta' => $meta],
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
        ),
        onFailure: fn($error, array $meta) => 'FAIL '.json_encode(
            ['error' => $error, 'meta' => $meta],
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
        ),
    ).PHP_EOL;

    echo 'debug: '.json_encode($result->toDebugArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT).PHP_EOL;
}
