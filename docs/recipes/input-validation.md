---
title: Input Validation
---

# Input Validation

Goal: validate input and keep the original branch metadata available for later steps.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok($input, ['request_id' => $requestId])
    ->ensure(fn (array $input) => isset($input['email']), 'Email is required')
    ->ensure(fn (array $input) => filter_var($input['email'], FILTER_VALIDATE_EMAIL) !== false, 'Email is invalid');
```

## Why this pattern works

- validation stays explicit
- failures short-circuit
- metadata survives the validation step

## Variation

If you need all field errors at once, switch to [Collecting batch errors](/recipes/collecting-batch-errors).
