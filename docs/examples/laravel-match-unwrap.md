---
title: Laravel Match and Unwrap
---

# Laravel Match and Unwrap

## Scenario

Finish Result pipelines for HTTP responses or domain values.

## Example

```php
$result = $service->execute($dto);

return $result->match(
    onSuccess: fn ($value) => response()->json(['ok' => true, 'data' => $value]),
    onFailure: fn ($error) => response()->json(['ok' => false, 'error' => (string) $error], 400),
);
```

## Expected behavior

- Both branches are handled explicitly.
- No hidden implicit fallback logic.

## Related pages

- [Matching and Unwrapping](/result/matching-unwrapping)
- [API Reference](/api)
