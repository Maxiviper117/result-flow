---
title: Batch Processing
---

# Batch Processing

## What this page is for

Use batch methods when you are processing collections where each item can independently succeed or fail.

## Start from raw items: `mapItems`, `mapAll`, `mapCollectErrors`

All three methods use callback contract:

```php
fn ($item, $key) => Result|value
```

Common behavior:
- Input keys are preserved.
- Plain callback values are wrapped as `Result::ok(value)`.
- Thrown exceptions are converted to `Result::fail(Throwable)`.

### `mapItems()`

Returns one `Result` per input key.

```php
$perItem = Result::mapItems($rows, fn (array $row, string $key) => validateRow($row, $key));
```

Use when downstream logic needs item-by-item status inspection.

### `mapAll()`

Fail-fast aggregate over raw items.

```php
$all = Result::mapAll($rows, fn (array $row) => persistRow($row));
```

Behavior:
- Stops at first failure.
- Success => `ok(array<key, value>)`
- Failure => `fail(firstError)`
- On failure, `value()` is `null`.

### `mapCollectErrors()`

Collect-all aggregate over raw items.

```php
$allErrors = Result::mapCollectErrors(
    $rows,
    fn (array $row, string $key) => validateRow($row, $key)
);
```

Behavior:
- Processes all items.
- Success => `ok(array<key, value>)`
- Failure => `fail(array<key, error>)`
- On failure, `value()` is `null`.

## Start from existing results: `combine`, `combineAll`

- `combine(array<Result>)`: fail-fast on first failing result.
- `combineAll(array<Result>)`: collect all errors from result list.

## Decision table

| Input shape | Need | Use |
|---|---|---|
| Raw items | Per-item statuses | `mapItems` |
| Raw items | Fail-fast aggregate | `mapAll` |
| Raw items | Collect-all aggregate | `mapCollectErrors` |
| Existing `Result[]` | Fail-fast aggregate | `combine` |
| Existing `Result[]` | Collect-all aggregate | `combineAll` |

## Related pages

- [Constructing Results](/result/constructing)
- [Error Handling](/result/error-handling)
- [API Reference](/api)
