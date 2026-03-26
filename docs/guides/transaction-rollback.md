---
title: Transaction Rollback
---

# Transaction Rollback

Use `thenUnsafe(...)` when the transaction boundary depends on exceptions bubbling out of the chain.

```php
DB::transaction(function () use ($dto) {
    return Result::ok($dto)
        ->thenUnsafe(new ValidateOrderAction)
        ->thenUnsafe(new PersistOrderAction)
        ->throwIfFail();
});
```

## Why this pattern works

- `thenUnsafe(...)` does not convert thrown exceptions into failures
- the transaction can roll back naturally
- `throwIfFail()` escalates explicit failure results at the edge

## Related pages

- [Chaining](/concepts/chaining)
- [Finalization boundaries](/concepts/finalization-boundaries)
