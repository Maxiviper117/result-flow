---
title: Transformers
---

# Transformers

## What this page is for

Use transformers at application boundaries where you need JSON, XML, or HTTP response output.

## `toArray()`

Returns raw shape:

```php
[
  'ok' => bool,
  'value' => mixed,
  'error' => mixed,
  'meta' => array,
]
```

## `toJson(int $options = 0)`

```php
$json = $result->toJson(JSON_PRETTY_PRINT);
```

Behavior:
- Uses `JSON_THROW_ON_ERROR`.
- Throws `JsonException` on encoding failure.

## `toXml(string $rootElement = 'result')`

```php
$xml = $result->toXml('api_response');
```

Behavior:
- Numeric array keys are converted to valid element names (`item0`, `item1`, ...).

## `toResponse()`

```php
$response = $result->toResponse();
```

Behavior:
- In Laravel with `response()` helper, returns JSON response object.
- Outside Laravel, returns normalized array containing status, headers, and body.

## Related pages

- [Matching and Unwrapping](/result/matching-unwrapping)
- [Sanitization Guide](/sanitization)
- [API Reference](/api)
