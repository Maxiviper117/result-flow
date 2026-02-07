---
title: Laravel Actions Pipeline
---

# Laravel Actions Pipeline

## Scenario

Run an ordered action pipeline with metadata propagation.

## Example

```php
$result = Result::ok($dto, ['request_id' => $rid])
    ->then([
        new ValidateAction,
        new AuthorizeAction,
        new PersistAction,
    ]);
```

## Expected behavior

- Step array is executed in order.
- First failure aborts remaining steps.

## Related pages

- [Chaining and Transforming](/result/chaining)
- [Internals](/guides/internals)
