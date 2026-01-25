<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

if (! function_exists('config')) {
    function config($key = null, $default = null)
    {
        if ($key === 'result-flow.debug') {
            return [
                'enabled' => true,
                'redaction' => '***REDACTED123***',
                'sensitive_keys' => ['api_*', '*token*', '?id', 'password'],
                'max_string_length' => 200,
                'truncate_strings' => true,
            ];
        }
        return $default;
    }
}

$meta = [
    'api_user' => 'secret_api_user',
    'session_token' => 'secret_session_token',
    'xid' => 'secret_xid',
    'user_password' => 'supersecret',
    'normal' => 'value',
    123 => 'numeric_key_value',
];

$result = Result::fail(new \RuntimeException('Manual test'), $meta);
$debug = $result->toArray();

echo "Debug output:\n";
echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
