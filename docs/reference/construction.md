---
title: Construction Reference
---

# Construction Reference

## `Result::ok(mixed $value, array $meta = []): Result`

Creates a success result.

```php
$result = Result::ok(['id' => 1], ['request_id' => 'r-1']);
```

## `Result::fail(mixed $error, array $meta = []): Result`

Creates a failure result.

```php
$result = Result::fail('Invalid state', ['step' => 'validate']);
```

You can also pass a structured error object such as a `DataTaggedError` subclass
when you want stable boundary serialization and class-based matching later.

## `Result::failTagged(string $code, string $message, mixed $payload = null, array $meta = [], ?Cause $cause = null): Result`

Creates a failure result containing a `DataTaggedError`.

- useful for quick structured failures
- good when you want predictable JSON/debug output
- for named domain errors, prefer a subclass of `DataTaggedError` and construct it via `::from(...)`

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::failTagged('E_USER_PERSIST', 'Unable to save user', ['email' => 'dev@example.com']);
```

### Named structured errors

```php
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\DataTaggedError;

final class UserPersistError extends DataTaggedError
{
	public const CODE = 'E_USER_PERSIST';
}

$result = Result::fail(UserPersistError::from('Unable to save user', ['email' => 'dev@example.com']));
```

## `Result::failWithValue(mixed $error, mixed $failedValue, array $meta = []): Result`

Creates a failure result and stores the value that caused it in metadata under `failed_value`.

## `Result::of(callable $fn): Result`

Runs a throwing callback and converts thrown exceptions to failure.

- return value -> success
- returned `Result` -> success containing that `Result` as the value
- thrown exception -> failure with the exception object

Choose this when the callback's success path returns a plain value and failure is expressed by throwing.

## `Result::defer(callable $fn): Result`

Runs a callback that may return a plain value, return a `Result`, or throw.

- plain value -> success
- returned `Result` -> returned as-is
- thrown exception -> failure

Choose this when the callback may already return `Result::ok(...)` or `Result::fail(...)` and you do not want nested `Result` values.

## `Result::retry(int $times, callable $fn, int $delay = 0, bool $exponential = false): Result`

Retries a callback with optional delay and exponential backoff.

## `Result::retryDefer(int $times, callable $fn, int $delay = 0, bool $exponential = false): Result`

Retries a `defer(...)`-style callback.

## `Result::retrier(): Retry`

Returns the fluent retry builder.

## `Result::bracket(callable $acquire, callable $use, callable $release): Result`

Runs an acquire/use/release flow.

### Behavior notes

- release is not called when acquire fails
- release is always attempted after use when acquire succeeds
- if use fails and release throws, use failure stays and the release exception is written to `meta['bracket.release_exception']`
- if use succeeds and release throws, the result becomes failure

## Related pages

- [Construction concepts](/concepts/constructing)
- [Retries concepts](/concepts/retries)
- [Resource safety](/concepts/resource-safety)
