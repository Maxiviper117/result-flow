---
title: Collecting Batch Errors
---

# Collecting Batch Errors

Goal: validate every item and return all failures keyed by item.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::mapCollectErrors($rows, function (array $row, string $key) {
    if (! filter_var($row['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
        return Result::fail("Invalid email at {$key}");
    }

    return Result::ok([
        'email' => strtolower($row['email']),
    ]);
});
```

## Why this pattern works

- every item is evaluated
- errors stay keyed
- partial success is not exposed on the failure branch

## Variation

Use `mapAll(...)` if the first failure should stop processing immediately.
