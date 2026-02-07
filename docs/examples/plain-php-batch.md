---
title: Plain PHP Batch Processing
---

# Plain PHP Batch Processing

```php
use Maxiviper117\ResultFlow\Result;

$rows = [
    'r1' => ['email' => 'good@example.com'],
    'r2' => ['email' => 'bad-email'],
    'r3' => ['email' => 'another@example.com'],
];

$validation = Result::mapCollectErrors($rows, function (array $row, string $key) {
    if (! str_contains($row['email'], '@')) {
        return Result::fail("Invalid email at {$key}");
    }

    return Result::ok($row);
});
```

Use:
- `mapAll` when first failure should stop processing.
- `mapCollectErrors` when all failures should be reported.

Related:
- [Batch Processing](/result/batch-processing)
- [API Reference](/api)
