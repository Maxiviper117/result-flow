---
title: API Reference
---

# API Reference

This page documents every public method on `Maxiviper117\ResultFlow\Result`.

Conventions used below:
- Callback signatures are shown in human-readable form.
- `meta` always refers to `array<string,mixed>`.
- Unless noted, callbacks receive both payload and metadata.

## Static constructors and aggregators

### `Result::ok(mixed $value, array $meta = []): Result`

Contract:
- Create success branch with `value` and `meta`.

Example:
```php
$ok = Result::ok(['id' => 1], ['request_id' => 'r-1']);
```

### `Result::fail(mixed $error, array $meta = []): Result`

Contract:
- Create failure branch with `error` and `meta`.

Example:
```php
$fail = Result::fail('Invalid state', ['step' => 'validate']);
```

### `Result::failWithValue(mixed $error, mixed $failedValue, array $meta = []): Result`

Contract:
- Create failure and inject failed input into `meta['failed_value']`.

Example:
```php
$fail = Result::failWithValue('Invalid email', ['email' => 'bad']);
```

### `Result::of(callable $fn): Result`

Contract:
- Run callback and capture thrown exceptions.

Behavior:
- return value => `ok(value)`
- throw => `fail(Throwable)`

Example:
```php
$user = Result::of(fn () => $repo->findOrFail($id));
```

### `Result::retry(int $times, callable $fn, int $delay = 0, bool $exponential = false): Result`

Contract:
- Simple retry entry point.

Behavior:
- Attempts operation up to `times` with optional delay/backoff.

Example:
```php
$result = Result::retry(3, fn () => callApi(), delay: 100, exponential: true);
```

### `Result::retrier(): Retry`

Contract:
- Return fluent retrier builder for advanced policies.
- Returns `Maxiviper117\ResultFlow\Support\Operations\Retry`.

Example:
```php
$result = Result::retrier()->maxAttempts(5)->jitter(50)->attempt(fn () => callApi());
```

### `Result::combine(array $results): Result`

Contract:
- Input: `array<Result<T, E>>`
- Output: `Result<array<T>, E>`

Behavior:
- Fail-fast on first failure.
- Merges metadata from processed results in order.

Example:
```php
$combined = Result::combine([$userResult, $accountResult]);
```

### `Result::combineAll(array $results): Result`

Contract:
- Input: `array<Result<T, E>>`
- Output: `Result<array<T>, array<E>>`

Behavior:
- Processes all results.
- Returns all errors in failure channel.

Example:
```php
$combined = Result::combineAll([$emailCheck, $passwordCheck, $profileCheck]);
```

### `Result::mapItems(array $items, callable $fn): array`

Contract:
- Callback: `fn ($item, $key) => Result|value`
- Output: `array<key, Result<success, failure|Throwable>>`

Behavior:
- Preserves input keys.
- Wraps plain values as `ok`.
- Converts thrown exceptions to `fail(Throwable)`.

Example:
```php
$mapped = Result::mapItems($rows, fn (array $row, string $key) => validateRow($row, $key));
```

### `Result::mapAll(array $items, callable $fn): Result`

Contract:
- Callback: `fn ($item, $key) => Result|value`
- Output: `Result<array<key, success>, failure|Throwable>`

Behavior:
- Fail-fast over raw items.
- On failure, `value()` is `null`.

Example:
```php
$all = Result::mapAll($rows, fn (array $row) => persistRow($row));
```

### `Result::mapCollectErrors(array $items, callable $fn): Result`

Contract:
- Callback: `fn ($item, $key) => Result|value`
- Output: `Result<array<key, success>, array<key, failure|Throwable>>`

Behavior:
- Evaluates all items.
- Returns keyed errors when any item fails.

Example:
```php
$allErrors = Result::mapCollectErrors($rows, fn (array $row, string $key) => validateRow($row, $key));
```

## State and value access

### `isOk(): bool`

Contract:
- True if result is success branch.

### `isFail(): bool`

Contract:
- True if result is failure branch.

### `value(): mixed`

Contract:
- Return success value or `null` when failed.

### `error(): mixed`

Contract:
- Return failure value or `null` when successful.

### `meta(): array`

Contract:
- Return current metadata map.

### `toArray(): array`

Contract:
- Return raw shape:
  `['ok' => bool, 'value' => mixed, 'error' => mixed, 'meta' => array]`

### `toDebugArray(?callable $sanitizer = null): array`

Contract:
- Return debug-safe serialized shape with sanitization.

Example:
```php
$debug = $result->toDebugArray();
```

## Metadata operations

### `tapMeta(callable $tap): Result`

Contract:
- Callback: `fn (array $meta): void`
- Side-effect only; does not change result.

### `mapMeta(callable $map): Result`

Contract:
- Callback: `fn (array $meta): array`
- Replaces metadata with callback output.

### `mergeMeta(array $meta): Result`

Contract:
- Merge metadata keys into current metadata.

Example:
```php
$result = Result::ok($dto, ['request_id' => 'r-1'])->mergeMeta(['step' => 'validated']);
```

## Tap and inspection methods

### `tap(callable $tap): Result`

Contract:
- Callback: `fn ($valueOrNull, $errorOrNull, array $meta): void`
- Runs on both branches.

### `onSuccess(callable $tap): Result`

Contract:
- Callback: `fn ($value, array $meta): void`
- Runs only on success.

### `inspect(callable $tap): Result`

Contract:
- Alias of `onSuccess`.

### `onFailure(callable $tap): Result`

Contract:
- Callback: `fn ($error, array $meta): void`
- Runs only on failure.

### `inspectError(callable $tap): Result`

Contract:
- Alias of `onFailure`.

Example:
```php
$result
    ->onSuccess(fn ($v) => logger()->info('ok', ['value' => $v]))
    ->onFailure(fn ($e) => logger()->warning('fail', ['error' => (string) $e]));
```

## Transforming and chaining

### `map(callable $map): Result`

Contract:
- Callback: `fn ($value, array $meta) => $newValue`
- Output: `Result<newValue, sameError>`

Behavior:
- Runs only on success.

### `mapError(callable $map): Result`

Contract:
- Callback: `fn ($error, array $meta) => $newError`
- Output: `Result<sameValue, newError>`

Behavior:
- Runs only on failure.

### `ensure(callable $predicate, mixed $error): Result`

Contract:
- Predicate: `fn ($value, array $meta): bool`
- Error can be value or factory callback.

Behavior:
- On success + false predicate => convert to failure.

Example:
```php
$validated = Result::ok($order)->ensure(fn ($o) => $o->total > 0, 'Invalid total');
```

### `then(callable|object|array $next): Result`

Contract:
- Step accepts `(value, meta)` and may return `Result` or plain value.
- Arrays of steps are supported.

Behavior:
- Runs only on success.
- Catches thrown exceptions and converts to failure.

### `flatMap(callable $fn): Result`

Contract:
- Alias of `then`.

### `thenUnsafe(callable|object $next): Result`

Contract:
- Same mapping contract as `then`, but no exception capture.

Behavior:
- Exceptions bubble to caller.

Example:
```php
$dbResult = Result::ok($dto)
    ->thenUnsafe(new ValidateAction)
    ->thenUnsafe(new PersistAction)
    ->throwIfFail();
```

## Failure branch handlers

### `otherwise(callable|object|array $next): Result`

Contract:
- Step accepts `(error, meta)` and may return `Result` or plain value.

Behavior:
- Runs only on failure.
- Returning plain value recovers to success.

### `catchException(array $handlers, ?callable $fallback = null): Result`

Contract:
- `handlers` map Throwable class => callback.
- Optional fallback for unmatched failures.

Behavior:
- Skips on success.
- Handles only Throwable failures by class match.

### `recover(callable $fn): Result`

Contract:
- Callback: `fn ($error, array $meta) => $successValue`
- Output branch is always success.

Example:
```php
$ok = maybeFailing()->recover(fn ($error) => ['fallback' => true]);
```

## Matching and unwrapping

### `match(callable $onSuccess, callable $onFailure): mixed`

Contract:
- Exhaustive branch handling.
- Both callbacks must return same conceptual output type.

### `matchException(array $exceptionHandlers, callable $onSuccess, callable $onUnhandled): mixed`

Contract:
- Exception-specific match for failure branch.

Behavior order:
1. success => `onSuccess`
2. Throwable failure with matching handler => that handler
3. otherwise => `onUnhandled`

### `unwrap(): mixed`

Contract:
- Return success value.
- Throw on failure.

### `unwrapOr(mixed $default): mixed`

Contract:
- Return success value or eager default.

### `unwrapOrElse(callable $fn): mixed`

Contract:
- Callback: `fn ($error, array $meta) => $default`
- Return success value or lazy default.

### `getOrThrow(callable $exceptionFactory): mixed`

Contract:
- Callback: `fn ($error, array $meta) => Throwable`
- Throw custom exception on failure.

### `throwIfFail(): Result`

Contract:
- Return same result on success.
- Throw when failure.

Example:
```php
$result->throwIfFail();
```

## Output transformers

### `toJson(int $options = 0): string`

Contract:
- Serialize `toArray()` shape to JSON.

Behavior:
- Uses `JSON_THROW_ON_ERROR`.

### `toXml(string $rootElement = 'result'): string`

Contract:
- Serialize result to XML document string.

### `toResponse(): mixed`

Contract:
- Convert to HTTP response shape.

Behavior:
- Laravel available => framework response object.
- Non-framework => normalized array with status/headers/body.

## Method selection quick table

| Need | Method |
|---|---|
| Build result from value/error | `ok`, `fail`, `failWithValue` |
| Wrap throwing call | `of` |
| Transform success value | `map` |
| Chain step returning Result | `then` / `flatMap` |
| Handle failure branch | `otherwise` / `catchException` |
| Recover to success | `recover` |
| Fail-fast combine | `combine` / `mapAll` |
| Collect-all combine | `combineAll` / `mapCollectErrors` |
| Final branch handling | `match` |
| Throwing extraction | `unwrap`, `getOrThrow`, `throwIfFail` |
| Boundary serialization | `toJson`, `toXml`, `toResponse` |

## Related pages

- [Result Guide](/result/)
- [Batch Processing](/result/batch-processing)
- [Examples](/examples/)
