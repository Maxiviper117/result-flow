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

## How it behaves

- plain value -> `ok(value)`
- returned `Result` -> returned as-is
- thrown exception -> `fail(Throwable)`

## When to use it

Use `defer(...)` when the callback is not under your control or already mixes value and `Result` returns.

Use `of(...)` when the callback always returns a plain success value.

## Related pages

- [Construction reference](/reference/construction)
- [Retries](/concepts/retries)
