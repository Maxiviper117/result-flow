---
title: Collections
---

# Collections

This group covers item mapping and aggregation.

## Quick Map

| Function | What it does |
| --- | --- |
| `combine` | Aggregates existing results and stops on the first failure |
| `combineAll` | Aggregates existing results and collects every failure |
| `mapItems` | Maps raw items to per-item `Result` values |
| `mapAll` | Maps raw items into one fail-fast result |
| `mapCollectErrors` | Maps raw items and collects keyed errors |

## combine

`combine(...)` takes an array of existing `Result` values and merges them into one aggregate result.

- success values are collected into an array
- the first failure stops processing
- metadata from processed results is merged in order

Use it when the collection should fail fast.

Shape:

```php
// Ok([$user, $account], meta: [...])
// or Fail($firstError, meta: [...])
```

Use:

```php
$combined = Result::combine([$userResult, $accountResult]);
```

## combineAll

`combineAll(...)` takes an array of existing `Result` values and keeps every failure.

- success values are collected into an array
- failure values are collected into an array of errors
- metadata from all processed results is merged in order

Use it when the caller needs the full set of failure values.

Shape:

```php
// Ok([$emailCheckValue, $passwordCheckValue, $profileCheckValue], meta: [...])
// or Fail([$emailError, $passwordError, $profileError], meta: [...])
```

Use:

```php
$combined = Result::combineAll([$emailCheck, $passwordCheck, $profileCheck]);
```

## mapItems

`mapItems(...)` maps each raw item to its own `Result` and keeps the original keys.

- plain return values are wrapped as success
- thrown exceptions become failure results
- keys stay aligned with the source array

Use it when the caller needs per-item results instead of one aggregate result.

Shape:

```php
// ['row-1' => Ok(...), 'row-2' => Fail(...)]
```

Use:

```php
$mapped = Result::mapItems($rows, fn (array $row, string $key) => validateRow($row, $key));
```

## mapAll

`mapAll(...)` maps raw items into one aggregate result and stops on the first failure.

- keyed success values are returned on success
- metadata is merged in processing order

Use it when every item must succeed for the whole collection to succeed.

Shape:

```php
// Ok(['row-1' => $savedRow1, 'row-2' => $savedRow2], meta: [...])
// or Fail($firstError, meta: [...])
```

Use:

```php
$result = Result::mapAll($rows, fn (array $row, string $key) => persistRow($row));
```

## mapCollectErrors

`mapCollectErrors(...)` maps raw items and returns keyed failures when any item fails.

- keyed success values are returned on success
- failure values stay keyed on the error branch
- every item is evaluated

Use it for validation-style flows where the caller needs the whole error set.

Shape:

```php
// Ok(['row-1' => $validRow1, 'row-2' => $validRow2], meta: [...])
// or Fail(['row-2' => $errorForRow2], meta: [...])
```

Use:

```php
$result = Result::mapCollectErrors($rows, fn (array $row, string $key) => validateRow($row, $key));
```

## See Also

- [Batch processing reference](/reference/batch-processing)
- [Kitchen sink overview](./)
