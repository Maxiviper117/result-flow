---
title: Batch Processing Reference
---

# Batch Processing Reference

```php
$result = Result::mapCollectErrors($rows, fn (array $row, string $key) => validateRow($row, $key));
```

## `combine(array $results): Result`

Combines existing `Result` values.

- success values are collected into an array
- the first failure stops processing
- metadata from processed results is merged in order

## `combineAll(array $results): Result`

Combines existing `Result` values, returns no success values when any failure exists, and collects all failures.

- success values are collected into an array
- failures are collected into an array of errors
- no success values are returned if any input fails
- metadata from all processed results is merged in order

## `mapItems(array $items, callable $fn): array`

Maps raw items to individual `Result` values.

- keys are preserved
- plain return values are wrapped as success
- thrown exceptions become failure results

## `mapAll(array $items, callable $fn): Result`

Maps raw items and fails fast on the first failure.

- keyed success values are returned on success
- metadata is merged in order

## `mapCollectErrors(array $items, callable $fn): Result`

Maps raw items and collects all failures by key.

- keyed success values are returned on success
- failure values stay keyed on the error branch
- partial success is not exposed when any failure exists

## Related pages

- [Batch processing concepts](/concepts/batch-processing)
- [Batch strategy guide](/guides/batch-strategy)
