---
title: Chaining
---

# Chaining

Chaining keeps the success branch moving until a step fails.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['total' => 42])
    ->ensure(fn (array $order) => $order['total'] > 0, 'Total must be positive')
    ->map(fn (array $order) => [...$order, 'tax' => 4.2])
    ->then(fn (array $order) => Result::ok($order, ['stage' => 'validated']));
```

## The basic rule

- `map(...)` transforms a success value into another success value.
- `then(...)` chains a step that may return a plain value or a `Result`.
- `thenUnsafe(...)` chains the same way, but exceptions bubble.
- `ensure(...)` turns a false predicate into a failure.
- `mapError(...)` only runs on failure.

## Why `then` captures exceptions

`then(...)` is the default pipeline tool. It makes the chain predictable by converting thrown exceptions into failure values instead of letting them escape unexpectedly.

When you need rollback semantics or intentional exception bubbling, use `thenUnsafe(...)`.

## Small rules that matter

- A failure short-circuits the rest of the chain.
- Plain return values are wrapped as `Result::ok(...)`.
- Callable arrays such as `[$service, 'handle']` stay a single step.
- Objects with `__invoke`, `handle`, or `execute` can be used as steps.

## Common mistakes

- Returning a `Result` from `map(...)`.
- Using `thenUnsafe(...)` without a clear boundary reason.
- Running `ensure(...)` after expensive work instead of early.

## Related pages

- [Failure handling](/concepts/failure-handling)
- [Deferred execution](/concepts/deferred-execution)
- [Chaining reference](/reference/chaining)
