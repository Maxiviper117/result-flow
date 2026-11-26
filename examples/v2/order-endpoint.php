<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';
require __DIR__.'/OrderEndpoint.php';

use ResultFlowExamples\OrderEndpoint;

$name = is_string($_POST['name'] ?? null) ? $_POST['name'] : '';
$traceId = is_string($_SERVER['HTTP_X_REQUEST_ID'] ?? null) ? $_SERVER['HTTP_X_REQUEST_ID'] : '';
$response = new OrderEndpoint()->handle($name, $traceId);
http_response_code($response['status']);
foreach ($response['headers'] as $header => $value) {
    header($header.': '.$value);
}
echo $response['body'];
