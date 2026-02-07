---
title: Sanitization Guide
---

# Sanitization Guide

Use sanitization to keep logs useful without leaking secrets.

## Preferred output method

Use `toDebugArray()` for diagnostics. It is designed for safe logging.

## What to redact

Typical sensitive keys:
- `password`
- `token`
- `secret`
- API keys
- session IDs

## Laravel config location

`config/result-flow.php`

Use this config for:
- redaction token/value
- key patterns for sensitive fields
- max string length and truncation behavior

## Custom sanitizer callback

```php
$debug = $result->toDebugArray(function (mixed $value): mixed {
    if (is_string($value) && strlen($value) > 64) {
        return substr($value, 0, 64).'...';
    }

    return $value;
});
```

## Related pages

- [Debugging and Meta](/debugging)
- [Metadata and Debugging](/result/metadata-debugging)
