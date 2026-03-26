---
title: Validate Then Persist
---

# Validate Then Persist

Use `ensure(...)` for cheap validation and `then(...)` for the work that should only run after the input is valid.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok($input, ['request_id' => $requestId])
    ->ensure(fn (array $input) => isset($input['email']), 'Email is required')
    ->ensure(fn (array $input) => filter_var($input['email'], FILTER_VALIDATE_EMAIL) !== false, 'Email is invalid')
    ->then(fn (array $input) => $repository->save($input))
    ->otherwise(fn ($error, array $meta) => Result::fail([
        'message' => (string) $error,
        'request_id' => $meta['request_id'] ?? null,
    ], $meta));
```

## Why this pattern works

- validation stays close to the input
- persistence only runs after validation passes
- metadata survives the full chain
- the failure shape is normalized once

## Related pages

- [Chaining](/concepts/chaining)
- [Error normalization](/guides/error-normalization)
