---
title: Transformers
---

# Transformers

_Reading time: ~4 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Task summary

Use transformers at boundaries where a `Result` must be converted to serialized output or HTTP response data.

Deep dives:
- Boundary strategy: [Finalization Boundaries](/result/compositions/finalization-boundaries)
- Metadata-safe diagnostics: [Metadata and Observability](/result/compositions/metadata-observability)
- Contracts: [API Reference](/api#output-transformers)

## Quick mental model

- `toArray` is raw branch shape.
- `toJson` and `toXml` are serialization boundaries.
- `toResponse` is framework edge conversion.
- Normalize failures before boundary conversion.
- JSON boundaries require JSON-encodable payloads.

## Primary methods

- `toArray`: raw shape for internal serialization.
- `toJson`: JSON payload with `JSON_THROW_ON_ERROR`.
- `toXml`: XML serialization with normalized element names.
- `toResponse`: HTTP boundary conversion; non-Laravel fallback uses a JSON string `body`.

## When to use `toJson` vs `toXml` vs `toResponse`

| Need | Method |
|---|---|
| JSON integration boundary | `toJson` |
| XML integration boundary | `toXml` |
| HTTP response boundary | `toResponse` |

## Worked flow (end-to-end)

### Input

```php
$result = Result::fail(['code' => 'NOT_FOUND', 'message' => 'Order not found']);
```

### Flow steps

1. Normalize failure shape for clients.
2. Convert to JSON for transport.
3. Convert to response at HTTP boundary.

### Output

- JSON sample:

```json
{
  "ok": false,
  "value": null,
  "error": {
    "message": "Order not found",
    "code": "NOT_FOUND"
  },
  "meta": []
}
```

- Response sample (non-Laravel mode):

```php
[
  'status' => 400,
  'headers' => ['Content-Type' => 'application/json'],
  'body' => '{"ok":false,"value":null,"error":{"code":"NOT_FOUND","message":"Order not found"},"meta":[]}',
]
```

## Copy-paste snippet

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$result = Result::fail(['code' => 'NOT_FOUND', 'message' => 'Order not found'])
    ->otherwise(fn (array $error, array $meta): Result => Result::fail([
        'message' => $error['message'],
        'code' => $error['code'],
    ], $meta));

$json = $result->toJson(JSON_PRETTY_PRINT);
$response = $result->toResponse();

echo $json . PHP_EOL;
print_r($response);
```

## Failure demo

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['resource' => fopen('php://memory', 'r')]);
$result->toJson();
```

Expected behavior: JSON encoding can throw when payload contains non-encodable values, including when `toResponse()` builds its non-Laravel fallback body.

## Common beginner mistakes

- Using `toResponse` deep inside service/domain logic.
- Serializing unnormalized error payloads.
- Expecting `toArray` to redact sensitive data.
- Forgetting `toJson` and fallback `toResponse` can throw on invalid encoding.

## Try it

- `php examples\defer\defer-test.php`
- `php examples\debug\debug-sanitization-demo.php`

## Related pages

- [Matching and Unwrapping](/result/matching-unwrapping)
- [Sanitization Guide](/sanitization)
- [API Reference](/api)
