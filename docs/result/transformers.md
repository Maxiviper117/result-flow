---
title: Transformers
---

# Result Transformers

Result Flow provides built-in methods to easily transform your `Result` objects into common formats like JSON, XML, or framework-agnostic HTTP responses. This is particularly useful at the boundaries of your application, such as in API controllers or CLI commands.

## JSON

Convert a result directly to a JSON string using `toJson()`.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['user_id' => 123], ['timestamp' => time()]);

echo $result->toJson();
// Output: {"ok":true,"value":{"user_id":123},"error":null,"meta":{"timestamp":...}}
```

You can pass standard `json_encode` options as the first argument:

```php
$result->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
```

## XML

Convert a result to an XML string using `toXml()`.

```php
$result = Result::ok(['user_id' => 123]);

echo $result->toXml();
/* Output:
<?xml version="1.0"?>
<result>
  <ok>1</ok>
  <value>
    <user_id>123</user_id>
  </value>
  <error/>
  <meta/>
</result>
*/
```

You can specify a custom root element name:

```php
echo $result->toXml('api_response');
// <api_response>...</api_response>
```

Arrays with numeric keys will be automatically prefixed with `item` to ensure valid XML tags (e.g., `<item0>`, `<item1>`).

## HTTP Responses

The `toResponse()` method transforms the result into a standardized response format.

### With Laravel

If you are using Laravel, `toResponse()` automatically returns a `Illuminate\Http\JsonResponse` object.

- **Success**: Returns status `200 OK`.
- **Failure**: Returns status `400 Bad Request`.

```php
// In a Laravel Controller
public function store(Request $request)
{
    return CreateUserAction::execute($request->all())
        ->toResponse();
}
```

### Without Frameworks

If the Laravel `response()` helper is not available, it returns a structured array suitable for manual response construction:

```php
$response = $result->toResponse();

// $response is:
// [
//     'status' => 200, // or 400
//     'headers' => ['Content-Type' => 'application/json'],
//     'body' => '{"ok":true...}'
// ]

http_response_code($response['status']);
header('Content-Type: application/json');
echo $response['body'];
```
