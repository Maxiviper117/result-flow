---
title: Kitchen Sink
---

# Kitchen Sink

This section is the full method tour.

Use it when you want every public `Result` method and the retry builder covered in one place, grouped by behavior. Start with the compact map, then open the category pages for the full explanation of each function.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok($input, ['request_id' => 'r-1'])
    ->ensure(fn (array $input) => isset($input['email']), 'Email is required')
    ->then(fn (array $input) => Result::ok($input, ['stage' => 'validated']))
    ->otherwise(fn ($error, array $meta) => Result::fail([
        'message' => (string) $error,
        'request_id' => $meta['request_id'] ?? null,
    ], $meta));
```

## Compact Map

| Group | Page |
| --- | --- |
| Constructors and resource safety | [Construction and entry points](./construction) |
| Collection operations | [Collections](./collections) |
| Branch state and metadata | [Branch state and metadata](./state-and-metadata) |
| Chaining and recovery | [Chaining and recovery](./chaining-and-recovery) |
| Finalization and output | [Finalization and output](./finalization-and-output) |
| Retry builder | [Retry builder](./retry-builder) |

## How To Use This Section

1. Open the category that matches the behavior you care about.
2. Read the short summary first.
3. Scan the function-by-function subsections.
4. Jump back to the narrower reference pages if you only need a quick lookup.

## Why This Exists

The reference pages are organized for lookup. This section is organized for comprehension.

The difference is deliberate:

- reference pages keep signatures and exact behavior easy to scan
- kitchen sink pages explain every public function in a single narrative section, grouped by behavior

## Start Here

- [Construction and entry points](./construction)
- [Collections](./collections)
- [Branch state and metadata](./state-and-metadata)
- [Chaining and recovery](./chaining-and-recovery)
- [Finalization and output](./finalization-and-output)
- [Retry builder](./retry-builder)
