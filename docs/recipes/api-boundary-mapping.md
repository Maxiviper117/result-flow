# API boundary mapping

Map domain outcomes to explicitly chosen response fields, keeping diagnostic metadata private.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::fail('invalid email', ['trace_id' => 'internal']);
$response = $result->match(
    onSuccess: fn ($user) => ['status' => 201, 'body' => ['id' => $user['id']]],
    onFailure: fn ($error) => ['status' => 422, 'body' => ['error' => $error]],
);
// ['status' => 422, 'body' => ['error' => 'invalid email']]
```

Related: [boundary reference](/reference/boundaries) and [validate then persist](/guides/validate-then-persist).

## Verify what crosses the boundary

Run `php examples/v2/boundary.php` from the checkout. The sample prints `{"status":422,"body":{"error":"Invalid input"}}`. The Result carries a trace ID internally. The failure handler accepts only the active error and explicitly builds the response, so that identifier stays out of the JSON. For a Laravel response object, use the [controller example](/laravel-boost#return-a-response-from-a-controller).
