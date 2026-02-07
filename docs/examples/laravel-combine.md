---
title: Laravel Combine
---

# Laravel Combine

## Scenario

Combine independent Result-returning lookups for one response payload.

## Example

```php
$result = Result::combine([
    $userService->find($id),
    $accountService->forUser($id),
    $prefsService->forUser($id),
])->map(fn (array $values) => [
    'user' => $values[0],
    'account' => $values[1],
    'preferences' => $values[2],
]);

return $result->toResponse();
```

## Expected behavior

- First failing dependency ends the combine flow.
- Successful branches aggregate in value order.

## Related pages

- [Constructing Results](/result/constructing)
- [Batch Processing](/result/batch-processing)
