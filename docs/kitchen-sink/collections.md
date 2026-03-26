---
title: Collections
---

# Collections

This group covers item mapping and aggregation.

## Quick Map

| Function           | What it does                                                                             |
| ------------------ | ---------------------------------------------------------------------------------------- |
| `combine`          | Aggregates existing results and stops on the first failure                               |
| `combineAll`       | Aggregates existing results, returns no successes on failure, and collects every failure |
| `mapItems`         | Maps raw items to per-item `Result` values                                               |
| `mapAll`           | Maps raw items into one fail-fast result                                                 |
| `mapCollectErrors` | Maps raw items and collects keyed errors                                                 |

## combine

`combine(...)` takes an array of existing `Result` values and merges them into one aggregate result.

```php
combine(array $results): self
```

### Inputs:

* `$results`: array of existing `Result` values to aggregate

### Behavior:

- success values are collected into an array
- the **first failure** stops processing
- metadata from processed results is merged in order

Use it when the collection should fail fast.

Use:

```php
use Maxiviper117\ResultFlow\Result;

// All succeed - returns combined values
$userResult = Result::ok(['id' => 1], ['step' => 'user']);
$profileResult = Result::ok(['bio' => 'Dev'], ['step' => 'profile']);

$result = Result::combine([$userResult, $profileResult]);
// Result: ok([['id' => 1], ['bio' => 'Dev']], ['step' => 'user', 'step' => 'profile'])

// First failure stops execution
$settingsResult = Result::fail('Settings not found');

$result = Result::combine([$userResult, $settingsResult, $profileResult]);
// Result: fail('Settings not found') - profileResult never checked!
```

## combineAll

`combineAll(...)` takes an array of existing `Result` values and keeps every failure. If any input fails, it returns a failure containing all errors and does not return success values.

```php
combineAll(array $results): self
```

### Inputs:

* `$results`: array of existing `Result` values to aggregate

### Behavior:

- success values are collected while iterating
- if any failure exists, the final result is a failure containing all errors; no success values are returned
- metadata from all processed results is merged in order

Use it when the caller needs the full set of failure values and can ignore partial successes on a failing aggregate.

Use:

```php
use Maxiviper117\ResultFlow\Result;

// All succeed - returns combined values
$result = Result::combineAll([
    Result::ok(['id' => 1]),
    Result::ok(['name' => 'John']),
]);
// Result: ok([['id' => 1], ['name' => 'John']])

// Multiple failures - collects ALL
$result = Result::combineAll([
    Result::fail('Email invalid'),
    Result::ok(['valid' => true]),
    Result::fail('Password too short'),
]);
// Result: fail(['Email invalid', 'Password too short'])
// ✅ Both errors collected, not stopped at first!
// ✅ No success values are returned when any failure exists
```

## mapItems

`mapItems(...)` maps each raw item to its own `Result` and keeps the original keys.

```php
mapItems(array $items, callable $fn): array
```

### Inputs:

* `$items`: input collection to map
* `$fn`: callback that may return a plain value or a `Result` and receives the item and key

### Behavior:

- plain return values are wrapped as success
- thrown exceptions become failure results
- keys stay aligned with the source array

Use it when the caller needs per-item results instead of one aggregate result.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$rows = [
    'user1' => ['email' => 'valid@example.com'],
    'user2' => ['email' => 'invalid-email'],
    'user3' => ['email' => 'also@valid.com']
];

$validator = fn (array $row, string $key): Result =>
    filter_var($row['email'] ?? null, FILTER_VALIDATE_EMAIL)
        ? Result::ok(['email' => strtolower($row['email'])])
        : Result::fail("Invalid email for {$key}");

$results = Result::mapItems($rows, $validator);
// Returns:
// [
//     'user1' => Result::ok(['email' => 'valid@example.com']),
//     'user2' => Result::fail('Invalid email for user2'),
//     'user3' => Result::ok(['email' => 'also@valid.com']),
// ]
```

## mapAll

`mapAll(...)` processes each item in a collection through a callback that returns a Result, with fail-fast behavior - it stops at the first failure and returns a single aggregated Result.

```php
mapAll(array $items, callable $fn): self
```

### Inputs:

* `$items`: input collection to map
* `$fn`: callback that may return a plain value or a `Result` and receives the item and key

### Behavior:

- keyed success values are returned on success
- stops at the first failure and returns a single aggregated Result
- metadata is merged in processing order

Use it when every item must succeed for the whole collection to succeed.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$csvRows = [
    1 => ['email' => 'alice@example.com', 'age' => 25],
    2 => ['email' => 'invalid-email', 'age' => 30],
    3 => ['email' => 'charlie@example.com', 'age' => 28],
];

function validateUser(array $row, int $lineNumber): Result {
    if (!filter_var($row['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
        return Result::fail("Line {$lineNumber}: Invalid email");
    }
    if ($row['age'] < 18) {
        return Result::fail("Line {$lineNumber}: Must be 18+");
    }
    return Result::ok([
        'email' => strtolower($row['email']),
        'age' => $row['age']
    ]);
}

// Fail-fast on first error
$result = Result::mapAll($csvRows, validateUser);

$result->match(
    onSuccess: fn ($users) => print("✅ All users valid: " . count($users) . " imported\n"),
    onFail: fn ($error) => print("❌ {$error}\n")
);

// Output:
// ❌ Line 2: Invalid email
// Stops here! Line 3 never processed
```

## mapCollectErrors

`mapCollectErrors(...)` processes each item in a collection through a callback that returns a Result, collecting ALL errors while processing everything. Returns a single Result with either all successes or all failures mapped by key.

```php
mapCollectErrors(array $items, callable $fn): self
```

### Inputs:

* `$items`: input collection to map
* `$fn`: callback that may return a plain value or a `Result` and receives the item and key

### Behavior:

- keyed success values are returned on success
- failure values stay keyed on the error branch
- never stops early, every item is evaluated

Use it for validation-style flows where the caller needs the whole error set.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$csvRows = [
    1 => ['email' => 'alice@example.com', 'age' => 25],
    2 => ['email' => 'invalid-email', 'age' => 30],
    3 => ['email' => 'charlie@example.com', 'age' => 15],
    4 => ['email' => 'bad-email', 'age' => 28],
];

function validateUser(array $row, int $lineNumber): Result {
    $errors = [];
    
    if (!filter_var($row['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email";
    }
    if ($row['age'] < 18) {
        $errors[] = "Must be 18+";
    }
    
    if (!empty($errors)) {
        return Result::fail(implode(', ', $errors));
    }
    
    return Result::ok(['email' => $row['email'], 'age' => $row['age']]);
}

$result = Result::mapCollectErrors($csvRows, validateUser);

$result->match(
    onSuccess: fn ($users) => print("✅ All valid: " . json_encode($users)),
    onFail: fn ($errors) => print("❌ Errors: " . json_encode($errors))
);

// Output on failure (collects ALL errors):
// ❌ Errors: {
//     "2": "Invalid email",
//     "3": "Must be 18+",
//     "4": "Invalid email"
// }
```

## See Also

- [Batch processing reference](/reference/batch-processing)
- [Kitchen sink overview](./)
