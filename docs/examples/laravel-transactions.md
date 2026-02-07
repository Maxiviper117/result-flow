---
title: Laravel Transactions
---

# Laravel Transactions

## Scenario

Use exception bubbling for rollback semantics.

## Example

```php
DB::transaction(function () use ($dto) {
    Result::ok($dto)
        ->thenUnsafe(new ValidateOrderAction)
        ->thenUnsafe(new PersistOrderAction)
        ->thenUnsafe(new ChargeCardAction)
        ->throwIfFail();
});
```

## Expected behavior

- Exceptions thrown by `thenUnsafe` steps bubble.
- Transaction rolls back naturally on thrown exceptions.
- `throwIfFail` escalates explicit `Result::fail` outcomes.

## Related pages

- [Chaining and Transforming](/result/chaining)
- [Error Handling](/result/error-handling)
