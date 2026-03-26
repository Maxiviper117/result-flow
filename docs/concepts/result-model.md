---
title: Result Model
---

# Result Model

Result Flow models work as one of two branches:

```text
Ok(value, meta)
Fail(error, meta)
```

That is the core idea. Everything else in the library preserves, transforms, or finalizes one of those two branches.

```php
use Maxiviper117\ResultFlow\Result;

$success = Result::ok(['id' => 1], ['request_id' => 'r-1']);
$failure = Result::fail('Invalid state', ['request_id' => 'r-1']);
```

## Why it exists

Exceptions are good for exceptional conditions. They are less useful when failure is expected and should be part of normal control flow.

Result Flow is for cases where you want:

- explicit success and failure branches
- typed chaining
- metadata that survives the flow
- a deliberate boundary where the flow becomes a plain value, a response, or a thrown exception

## How it behaves

- `value()` returns the success payload on `Ok`, otherwise `null`.
- `error()` returns the failure payload on `Fail`, otherwise `null`.
- `meta()` is always available.
- Branch-specific methods skip the other branch entirely.

## Why metadata is part of the model

Metadata carries context that should survive the chain:

- request IDs
- operation names
- source keys
- retry information
- debug context

Treat it as branch data, not logging decoration.

## Common mistakes

- Using `Result` only as a wrapper around exceptions.
- Dropping metadata too early.
- Unwrapping before the actual boundary.

## Related pages

- [Constructing results](/concepts/constructing)
- [Chaining](/concepts/chaining)
- [Metadata](/concepts/metadata)
- [Finalization boundaries](/concepts/finalization-boundaries)
