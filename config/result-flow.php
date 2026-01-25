<?php

declare(strict_types=1);

return [
    'debug' => [
        // Enable or disable built-in sanitization in toDebugArray().
        'enabled' => true,

        // Override the string used when redacting sensitive values in meta/debug output.
        'redaction' => '***REDACTED***',

        // Keys that should be redacted (case-insensitive, partial matches allowed).
        'sensitive_keys' => ['password', 'pass', 'secret', 'token', 'api_key', 'apikey', 'ssn', 'card', 'authorization'],

        // Maximum length for strings before truncation in debug output.
        'max_string_length' => 200,

        // Whether to truncate strings longer than max_string_length.
        'truncate_strings' => true,

        // Map error codes/classes to log levels for debug/log helpers.
        // Supports class-string keys for Throwable types and int/string codes.
        'log_level_map' => [
            // \RuntimeException::class => 'error',
            // 404 => 'notice',
            // 'E_TIMEOUT' => 'warning',
        ],

        // Default log level when no mapping matches (null disables log_level output).
        'default_log_level' => 'error',
    ],
];
