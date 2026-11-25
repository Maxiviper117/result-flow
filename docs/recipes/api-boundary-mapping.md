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
