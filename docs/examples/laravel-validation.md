---
title: Laravel Validation
---

# Laravel Validation

## Scenario

Return full validation errors across multiple checks.

## Example

```php
$checks = Result::mapCollectErrors([
    'email' => $request->input('email'),
    'password' => $request->input('password'),
    'age' => $request->input('age'),
], function (mixed $value, string $field) {
    return match ($field) {
        'email' => filter_var($value, FILTER_VALIDATE_EMAIL)
            ? Result::ok($value)
            : Result::fail('Invalid email'),
        'password' => is_string($value) && strlen($value) >= 8
            ? Result::ok($value)
            : Result::fail('Password too short'),
        'age' => is_numeric($value) && (int) $value >= 18
            ? Result::ok((int) $value)
            : Result::fail('Age must be >= 18'),
    };
});

return $checks->toResponse();
```

## Expected behavior

- All fields are evaluated.
- Failure payload contains keyed errors by field.

## Related pages

- [Batch Processing](/result/batch-processing)
- [API Reference](/api)
