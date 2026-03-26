---
title: Failure Handling
---

# Failure Handling

Failure handling is where a branch can stay failed, recover, or be finalized.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::fail('timeout')
    ->mapError(fn (string $error) => ['code' => 'TIMEOUT', 'message' => $error]);
```

## The main tools

- `mapError(...)` changes the failure payload.
- `otherwise(...)` handles the failure branch and can either recover or keep failing.
- `catchException(...)` handles failure values that are `Throwable` instances by class.
- `recover(...)` always returns success.
- `throwIfFail(...)` converts failure back into exception-style control flow.

## How to think about it

Use `otherwise(...)` when the next step is still part of the Result flow.

Use `recover(...)` only when you intentionally want to stop carrying the failure branch forward.

Use `throwIfFail(...)` at a boundary that expects exceptions, such as a transaction closure.

## Common mistakes

- Recovering too early and hiding useful error context.
- Mixing unrelated failure shapes in one chain.
- Using `throwIfFail(...)` deep inside the domain instead of at the edge.

## Related pages

- [Finalization boundaries](/concepts/finalization-boundaries)
- [Failure handling reference](/reference/failure-handling)
