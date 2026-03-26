---
title: Construction and Entry Points
---

# Construction and Entry Points

This group covers the functions that create a result, retry work, or guard a resource lifecycle.

## Quick Map

| Function | What it does |
| --- | --- |
| `ok` | Creates a success result |
| `fail` | Creates a failure result |
| `failWithValue` | Creates a failure result and stores the rejected input in metadata |
| `of` | Runs a throwing callback and converts the outcome to `Result` |
| `defer` | Normalizes a callback that may return a value, a `Result`, or throw |
| `retry` | Runs a callback with a simple retry policy |
| `retryDefer` | Retries a `defer`-style callback |
| `retrier` | Returns the fluent retry builder |
| `bracket` | Runs acquire/use/release with cleanup guarantees |

## ok

`ok(...)` creates a success branch with the provided value and metadata.

Use it when you already know the branch is successful and you want the chain to continue from that success state.

Shape:

```php
// Ok(['id' => 1], meta: ['request_id' => 'r-1'])
```

Use:

```php
$result = Result::ok(['id' => 1], ['request_id' => 'r-1']);
```

## fail

`fail(...)` creates a failure branch with the provided error payload and metadata.

Use it when failure is already known and should remain explicit instead of being thrown.

Shape:

```php
// Fail('Invalid state', meta: ['step' => 'validate'])
```

Use:

```php
$result = Result::fail('Invalid state', ['step' => 'validate']);
```

## failWithValue

`failWithValue(...)` creates a failure and stores the rejected value in `meta['failed_value']`.

Use it when the caller may need the input that caused the failure, such as validation or transform pipelines.

Shape:

```php
// Fail('Invalid email', meta: ['failed_value' => ['email' => 'bad@example']])
```

Use:

```php
$result = Result::failWithValue('Invalid email', ['email' => 'bad@example']);
```

## of

`of(...)` runs a callback that should return a plain success value.

- returned value becomes success
- thrown exception becomes failure

Use it when the callback always returns a plain value on success and you want exceptions converted into a failure branch.

Shape:

```php
// Ok($value, meta: [...])
// or Fail($throwable, meta: [...])
```

Use:

```php
$result = Result::of(fn () => $repository->find($id));
```

## defer

`defer(...)` runs a callback that may return a plain value, return a `Result`, or throw.

- plain value becomes success
- returned `Result` is returned as-is
- thrown exception becomes failure

Use it when the callback already mixes values and `Result` returns.

Shape:

```php
// Ok($value, meta: [...])
// or a returned Result from the callback
// or Fail($throwable, meta: [...])
```

Use:

```php
$result = Result::defer(fn () => fetchUser($id));
```

## retry

`retry(...)` runs a callback with a simple, bounded retry policy.

Use it when the callback already fits the retry contract and you only need attempt count, delay, and optional exponential backoff.

Shape:

```php
// Ok($value, meta: [...])
// or Fail($lastError, meta: [...])
```

Use:

```php
$result = Result::retry(3, fn () => callApi(), delay: 100, exponential: true);
```

## retryDefer

`retryDefer(...)` combines retry policy with `defer(...)` normalization.

Each attempt may return a value, return a `Result`, or throw. The attempt result is normalized first, then the retry policy decides whether to continue.

Use it when you want a retry loop around mixed callback behavior.

Shape:

```php
// Ok($value, meta: [...])
// or Fail($lastError, meta: [...])
```

Use:

```php
$result = Result::retryDefer(3, fn () => callExternalApi($payload), delay: 100);
```

## retrier

`retrier()` returns the fluent retry builder.

Use it when you need retry predicates, jitter, hooks, or attempt metadata rather than the simple helper methods.

Shape:

```php
// Retry builder instance
```

Use:

```php
$result = Result::retrier()
    ->maxAttempts(5)
    ->delay(100)
    ->attempt(fn () => callApi());
```

## bracket

`bracket(...)` runs acquire/use/release with cleanup guarantees.

- if acquire fails, release is not called
- if use fails, release is still attempted
- if release throws after a use failure, the original failure remains and the release exception is stored in metadata
- if use succeeds and release throws, the result becomes failure

Use it for resources that must be cleaned up even when the use step fails.

Shape:

```php
// Ok($value, meta: [...])
// or Fail($error, meta: [..., 'release_error' => ...])
```

Use:

```php
$result = Result::bracket(
    acquire: fn () => fopen($path, 'r'),
    use: fn ($handle) => fread($handle, 100),
    release: fn ($handle) => fclose($handle),
);
```

## See Also

- [Kitchen sink overview](./)
- [Retry builder](./retry-builder)
