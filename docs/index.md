---
title: Result Flow
---

# Result Flow

Result Flow gives PHP 8.2+ a small, explicit `Result` type for success, failure, and metadata.

The model is simple:

```text
Ok(value, meta) | Fail(error, meta)
```

That simplicity is the point. It keeps ordinary failures in the type system, so you can compose work, preserve context, and finish at a boundary on purpose.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['email' => 'dev@example.com'], ['request_id' => 'r-1'])
    ->ensure(fn (array $input) => isset($input['email']), 'Email is required')
    ->then(fn (array $input) => Result::ok([
        'email' => strtolower($input['email']),
    ], ['operation' => 'normalize-email']))
    ->otherwise(fn ($error, array $meta) => Result::fail([
        'message' => (string) $error,
        'request_id' => $meta['request_id'] ?? null,
    ], $meta));

// Finish explicitly at the edge.
$payload = $result->match(
    onSuccess: fn (array $value, array $meta) => ['ok' => true, 'data' => $value, 'meta' => $meta],
    onFailure: fn ($error, array $meta) => ['ok' => false, 'error' => $error, 'meta' => $meta],
);
```

## Start here

1. [Getting started](/getting-started)
2. [Kitchen sink](/kitchen-sink/)
3. [Concepts overview](/concepts/)
4. [Reference overview](/reference/)

## What to read next

- Learn the mental model in [Result model](/concepts/result-model)
- Get the full method tour in [Kitchen sink](/kitchen-sink/)
- See the common flow shape in [Chaining](/concepts/chaining)
- Learn where to stop in [Finalization boundaries](/concepts/finalization-boundaries)
- Look up exact signatures in [Reference](/reference/)
- Read practical patterns in [Guides](/guides/)
- Jump to concrete problems in [Recipes](/recipes/)
- If you use Boost, read [Laravel Boost](/laravel-boost)
