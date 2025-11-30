<?php

declare(strict_types=1);

return [
    'debug' => [
        // Override the string used when redacting sensitive values in meta/debug output.
        'redaction' => '***REDACTED***',

        // Keys that should be redacted (case-insensitive, partial matches allowed).
        'sensitive_keys' => ['password', 'pass', 'secret', 'token', 'api_key', 'apikey', 'ssn', 'card', 'authorization'],

        // Maximum length for strings before truncation in debug output.
        'max_string_length' => 200,
    ],
];
