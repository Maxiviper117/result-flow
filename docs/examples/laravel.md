---
title: Laravel Workflow
---

# Laravel Workflow

## Scenario

Controller/service pipeline with validation, persistence, and explicit failure mapping.

## Example

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok($request->all(), ['request_id' => (string) Str::uuid()])
    ->ensure(fn (array $input) => isset($input['email']), 'Email is required')
    ->then(fn (array $input) => $userService->create($input))
    ->otherwise(fn ($error, array $meta) => Result::fail([
        'message' => (string) $error,
        'request_id' => $meta['request_id'] ?? null,
    ], $meta));

return $result->toResponse();
```

## Expected behavior

- Success returns HTTP 200 response payload.
- Failure returns HTTP 400 response payload.
- Metadata remains available for diagnostics.

## Related pages

- [Getting Started](/getting-started)
- [Laravel Boost](/laravel-boost)
- [Error Handling](/result/error-handling)
- [API Reference](/api)
