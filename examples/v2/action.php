<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

$validate = fn (string $name): Result => $name === ''
    ? Result::fail('Name is required') : Result::ok($name);
$save = fn (string $name): Result => Result::ok(['id' => 42, 'name' => $name]);
$result = Result::ok($argv[1] ?? ' Ada ', ['trace_id' => 'example'])
    ->map(fn (string $name): string => trim($name))
    ->andThen($validate)
    ->andThen($save);

echo $result->match(
    onSuccess: fn (array $order): string => "Saved {$order['name']}\n",
    onFailure: fn (string $error): string => "Failed: {$error}\n",
);
