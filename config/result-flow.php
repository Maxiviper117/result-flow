<?php

declare(strict_types=1);

return [
    'debug' => [
        // Enable or disable built-in sanitization in toDebugArray().
        'enabled' => true,

        // Override the string used when redacting sensitive values in meta/debug output.
        'redaction' => '***REDACTED***',

        // Keys that should be redacted (case-insensitive, supports glob patterns '*' and '?' ; plain words are treated as substrings).
        'sensitive_keys' => ['password', 'pass', 'secret', 'token', 'api_key', 'apikey', 'ssn', 'card', 'authorization'],

        // Maximum length for strings before truncation in debug output.
        'max_string_length' => 200,

        // Whether to truncate strings longer than max_string_length.
        'truncate_strings' => true,
    ],
];
