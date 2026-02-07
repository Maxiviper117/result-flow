---
title: Laravel Debugging
---

# Laravel Debugging

## Scenario

Log failures with sanitized debug payloads.

## Example

```php
$result = $service->execute($input)
    ->onFailure(function ($error, array $meta) {
        logger()->warning('service-failure', [
            'error' => (string) $error,
            'meta' => $meta,
        ]);
    });

logger()->info('result-debug', $result->toDebugArray());
```

## Expected behavior

- Sensitive data is redacted when using `toDebugArray()`.
- Failure context remains traceable via metadata.

## Related pages

- [Metadata and Debugging](/result/metadata-debugging)
- [Sanitization Guide](/sanitization)
