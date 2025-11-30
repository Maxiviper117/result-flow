<?php

declare(strict_types=1);

/**
 * Demonstrates chaining with action objects (handle/execute methods),
 * exception bubbling via thenUnsafe, and recovery using otherwise().
 *
 * Run with:
 * php examples/actions.php
 */

require __DIR__.'/../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

final class ValidateCartAction
{
    public function handle(array $order, array $meta): Result
    {
        if (empty($order['items'])) {
            return Result::fail('Cart is empty', $meta);
        }

        if (($order['total'] ?? 0) <= 0) {
            return Result::fail('Total must be positive', $meta);
        }

        return Result::ok($order, $meta + ['validated' => true]);
    }
}

final class ReserveInventoryAction
{
    public function handle(array $order, array $meta): Result
    {
        $sku = $order['items'][0] ?? 'unknown';
        $available = $sku !== 'out-of-stock';

        if (! $available) {
            return Result::fail('Inventory unavailable', $meta + ['sku' => $sku]);
        }

        return Result::ok($order, $meta + ['reservation_id' => 'res-'.uniqid()]);
    }
}

final class PersistOrderAction
{
    public function execute(array $order, array $meta): Result
    {
        // Pretend persistence; in real code this could throw DB exceptions.
        return Result::ok(
            $order + ['id' => 'ord-'.uniqid()],
            $meta + ['saved' => true],
        );
    }
}

final class ChargePaymentAction
{
    public function handle(array $order, array $meta): Result
    {
        // Simulate a payment failure that should bubble up as an exception.
        if (($order['payment_token'] ?? '') === 'tok_throw') {
            throw new RuntimeException('Gateway unavailable');
        }

        if (($order['payment_token'] ?? '') !== 'tok_chargeable') {
            return Result::fail('Card declined', $meta);
        }

        return Result::ok($order + ['status' => 'paid'], $meta + ['charged' => true]);
    }
}

function processOrder(array $order): Result
{
    return Result::ok($order, ['flow' => 'actions'])
        ->then(new ValidateCartAction)
        ->then(new ReserveInventoryAction)
        ->then(new PersistOrderAction)
        // thenUnsafe lets exceptions bubble; throwIfFail escalates failures to exceptions
        ->thenUnsafe(new ChargePaymentAction)
        ->throwIfFail()
        ->otherwise(function ($error, array $meta) {
            // Recovery/fallback path; return Result or plain value
            return Result::ok(
                ['status' => 'queued', 'reason' => $error],
                $meta + ['queued' => true]
            );
        });
}

$orders = [
    'happy-path' => [
        'items' => ['book'],
        'total' => 29.50,
        'payment_token' => 'tok_chargeable',
    ],
    'invalid-cart' => [
        'items' => [],
        'total' => 0,
        'payment_token' => 'tok_chargeable',
    ],
    'inventory-fail' => [
        'items' => ['out-of-stock'],
        'total' => 15,
        'payment_token' => 'tok_chargeable',
    ],
    'payment-declined' => [
        'items' => ['pen'],
        'total' => 5,
        'payment_token' => 'tok_bad_card',
    ],
    'payment-throws' => [
        'items' => ['notebook'],
        'total' => 12,
        'payment_token' => 'tok_throw',
    ],
];

foreach ($orders as $label => $order) {
    echo PHP_EOL.'['.$label.']'.PHP_EOL;

    try {
        $result = processOrder($order);

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
    } catch (Throwable $e) {
        echo 'EXC  '.json_encode(
            ['error' => $e->getMessage(), 'type' => $e::class],
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
        ).PHP_EOL;
    }
}
