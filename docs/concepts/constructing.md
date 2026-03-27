---
title: Constructing Results
---

# Constructing Results

Construction chooses the first branch and sets the initial metadata.

```php
use Maxiviper117\ResultFlow\Result;

$payload = ['id' => 1];

$result = Result::ok($payload, ['request_id' => 'r-1']);
```

## What the constructors do

- `ok(...)` creates a success branch.
- `fail(...)` creates a failure branch.
- `failWithValue(...)` stores the failed input in metadata under `failed_value`.
- `of(...)` wraps a throwing callback and always wraps the callback return value as success.
- `defer(...)` normalizes callbacks that may return a value, a `Result`, or throw.

## Why `defer` exists

`of(...)` is for callbacks that always return a plain value on success.

`defer(...)` is broader. Use it when the callback may already return a `Result`, because it preserves that result instead of wrapping it again.

That difference matters:

```php
use Maxiviper117\ResultFlow\Result;

$of = Result::of(fn () => Result::fail('nope'));
$defer = Result::defer(fn () => Result::fail('nope'));

$of->isOk();      // true
$defer->isFail(); // true
```

`of(...)` treats the returned `Result` as plain data.

`defer(...)` treats the returned `Result` as the result to continue with.

## What to remember

- Choose the branch explicitly when you already know it.
- Use `failWithValue(...)` when the failed input matters.
- Use `of(...)` for plain-value callbacks that may throw.
- Use `defer(...)` for callbacks that may already return `Result`.
- Start metadata early if the flow will need correlation later.

## Common mistakes

- Using `of(...)` when the callback may return a `Result`.
- Constructing a result without any metadata, then trying to recover context later.

## Related pages

- [Result model](/concepts/result-model)
- [Deferred execution](/concepts/deferred-execution)
- [Construction reference](/reference/construction)
