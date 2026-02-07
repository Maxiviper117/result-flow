---
title: Laravel Actions
---

# Laravel Actions

## Scenario

Compose small action classes that return `Result`.

## Example

```php
$result = Result::ok($dto)
    ->then(new ValidateUserAction)
    ->then(new PersistUserAction)
    ->then(new NotifyUserAction);

return $result->toResponse();
```

## Expected behavior

- Each action has explicit success/failure output.
- Pipeline short-circuits automatically on failure.

## Related pages

- [Chaining and Transforming](/result/chaining)
- [Usage Patterns](/guides/patterns)
