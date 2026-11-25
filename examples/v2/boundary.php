<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

$result = Result::fail('Invalid input', ['trace_id' => 'internal']);
$response = $result->match(
    onSuccess: fn ($value) => ['status' => 200, 'body' => ['value' => $value]],
    onFailure: fn ($error) => ['status' => 422, 'body' => ['error' => $error]],
);
echo json_encode($response, JSON_THROW_ON_ERROR)."\n";
