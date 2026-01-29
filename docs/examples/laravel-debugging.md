---
title: Laravel debugging + sanitization
---

# Laravel debugging + sanitization

This example shows how to log Result details safely using `toDebugArray()` and how to tune sanitization in Laravel.

## Logging a Result

```php
use Illuminate\Support\Facades\Log;
use Maxiviper117\ResultFlow\Result;

$result = Result::fail(new RuntimeException('Payment gateway down'), [
    'request_id' => 'r-123',
    'token' => 'secret-token',
]);

Log::info('payment.result', $result->toDebugArray());
```

`toDebugArray()` redacts sensitive keys (like `token`) and truncates long strings.

## Custom sanitizer (per call)

```php
$debug = $result->toDebugArray(function ($value) {
    if (is_string($value)) {
        return substr($value, 0, 8).'...';
    }

    return $value;
});

Log::debug('payment.debug', $debug);
```

## Laravel config override

Publish the config:

```bash
php artisan vendor:publish --tag=result-flow-config
```

Then adjust `config/result-flow.php`:

```php
return [
    'debug' => [
        'enabled' => true,
        'redaction' => '[redacted]',
        'sensitive_keys' => ['token', 'secret', 'authorization'],
        'max_string_length' => 64,
        'truncate_strings' => true,
    ],
];
```

Notes:
- Redaction uses glob patterns (`*`, `?`) and case-insensitive matching.
- If `enabled` is false, no redaction or truncation is performed.

## Result functions used

- `fail()`, `toDebugArray()`
