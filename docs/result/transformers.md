---
title: Transformers
---

# Result Transformers

Result Flow provides methods to transform `Result` objects into JSON, XML, or HTTP responses. These are meant for application boundaries (controllers, CLI output, API adapters) where you need a concrete output format.

## JSON (`toJson()`)

Convert a result directly to a JSON string.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['user_id' => 123], ['timestamp' => time()]);

echo $result->toJson();
// {"ok":true,"value":{"user_id":123},"error":null,"meta":{"timestamp":...}}
```

You can pass standard `json_encode` options as the first argument:

```php
$result->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
```

Details:
- The JSON payload shape matches `toArray()`.
- `toJson()` uses `JSON_THROW_ON_ERROR`, so it can throw `JsonException` if encoding fails.
- Use `toDebugArray()` instead of `toJson()` when you need sanitization.

## XML (`toXml()`)

Convert a result to XML.

```php
$result = Result::ok(['user_id' => 123]);

echo $result->toXml();
```

You can specify a custom root element name:

```php
echo $result->toXml('api_response');
```

Details:
- Arrays with numeric keys are prefixed with `item` to ensure valid XML tags (e.g., `<item0>`, `<item1>`).
- Values are cast to strings, so complex objects should be converted to arrays before calling `toXml()`.

## HTTP responses (`toResponse()`)

Transform a result into a response shape.

### With Laravel

If the Laravel `response()` helper is available, `toResponse()` returns a `JsonResponse`:

- Success -> status 200
- Failure -> status 400

```php
// In a Laravel Controller
public function store(Request $request)
{
    return CreateUserAction::execute($request->all())
        ->toResponse();
}
```

### Without a framework

If `response()` is not available, it returns a structured array:

```php
$response = Result::fail('bad')->toResponse();

// [
//   'status' => 400,
//   'headers' => ['Content-Type' => 'application/json'],
//   'body' => '{"ok":false,"value":null,"error":"bad","meta":[]}'
// ]
```

The payload in `body` is the same shape as `toArray()` and `toJson()`.
