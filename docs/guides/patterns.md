---
title: Usage Patterns
---

# Usage Patterns

## What this page is for

Use these patterns when designing pipelines that stay readable under growth.

## Pattern: Validate then persist

```php
$result = Result::ok($input, ['request_id' => $requestId])
    ->ensure(fn (array $data) => isset($data['email']), 'Missing email')
    ->then(fn (array $data) => validateUserData($data))
    ->then(fn (array $valid) => saveUser($valid));
```

Why this works:
- Validation remains in success branch.
- Failure short-circuit is automatic.
- Metadata remains attached for logging.

## Pattern: Fail-fast batch write

```php
$writeResult = Result::mapAll($rows, fn (array $row) => writeRow($row));
```

Use when first failure should stop further processing.

## Pattern: Full error reporting

```php
$validation = Result::mapCollectErrors($rows, fn (array $row, string $key) => validateRow($row, $key));
```

Use when you need all failures to return to caller.

## Pattern: Exception boundary at adapter edge

```php
$serviceResult = Result::of(fn () => $sdk->execute($payload))
    ->mapError(fn (Throwable $e) => new DomainError($e->getMessage()));
```

Use to normalize third-party exception behavior into domain failure values.

## Pattern: Transaction rollback semantics

```php
DB::transaction(function () use ($dto) {
    return Result::ok($dto)
        ->thenUnsafe(new ValidateOrder)
        ->thenUnsafe(new SaveOrder)
        ->throwIfFail();
});
```

Use `thenUnsafe` when exceptions must bubble for rollback semantics.

## Related pages

- [Anti-Patterns](/guides/anti-patterns)
- [Batch Processing](/result/batch-processing)
- [Error Handling](/result/error-handling)
