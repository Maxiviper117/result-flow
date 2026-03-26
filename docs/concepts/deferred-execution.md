---
title: Deferred Execution
---

# Deferred Execution

`defer(...)` lets a callback return a value, return a `Result`, or throw.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::defer(fn () => fetchUser($id));
```

## Why it exists

It reduces ceremony at call sites that already have mixed behavior:

- some paths return plain values
- some paths already return `Result`
- some paths throw

`defer(...)` normalizes all three cases into one `Result` shape.

It also differs from `of(...)` in one important way: `defer(...)` preserves a returned `Result` instead of wrapping it as a success value.

## How it behaves

- plain value -> `ok(value)`
- returned `Result` -> returned as-is
- thrown exception -> `fail(Throwable)`

Compare that with `of(...)`:

- plain value -> `ok(value)`
- returned `Result` -> `ok(Result(...))`
- thrown exception -> `fail(Throwable)`

## When to use it

Use `defer(...)` when the callback is not under your control or already mixes value and `Result` returns.

Use `of(...)` when the callback always returns a plain success value.

If you are unsure whether a callback might already return `Result`, prefer `defer(...)` to avoid nested `Result` values.

## Related pages

- [Construction reference](/reference/construction)
- [Retries](/concepts/retries)
