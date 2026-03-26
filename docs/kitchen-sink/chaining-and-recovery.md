---
title: Chaining and Recovery
---

# Chaining and Recovery

This group covers the functions that transform the success or failure branch, then move the flow forward.

## Quick Map

| Function         | What it does                            |
| ---------------- | --------------------------------------- |
| `map`            | Transforms the success value            |
| `ensure`         | Converts a false predicate into failure |
| `mapError`       | Transforms the failure value            |
| `otherwise`      | Runs only on failure and may recover    |
| `catchException` | Matches Throwable failures by class     |
| `recover`        | Converts failure into success           |
| `then`           | Chains a step with exception capture    |
| `flatMap`        | Alias of `then`                         |
| `thenUnsafe`     | Chains a step without exception capture |

## map

`map(...)` transforms the success value and leaves the failure branch unchanged.

```php
map(callable $map): self
```

### Inputs:

* `$map`: callback that receives the success value and metadata

### Behavior:

- runs only on `Ok`
- returns a new success result with the mapped value
- preserves metadata

Use it for pure success-value transforms.

Use:

```php
$result = Result::ok(['total' => 42])
    ->map(fn (array $order) => [...$order, 'tax' => 4.2]);
```

## ensure

`ensure(...)` keeps a success only when the predicate returns `true`.

```php
ensure(callable $predicate, mixed $error): self
```

### Inputs:

* `$predicate`: callback that receives the success value and metadata and returns a boolean
* `$error`: failure value or failure factory used when the predicate returns `false`

### Behavior:

- runs only on `Ok`
- returns the original success when the predicate passes
- converts to failure when the predicate fails
- the error may be a value or a factory callback

Use it for inline validation that should stay in the chain.

Use:

```php
$result = Result::ok(['email' => 'dev@example.com'])
    ->ensure(fn (array $input) => isset($input['email']), 'Email is required');
```

## mapError

`mapError(...)` transforms the failure value and leaves success unchanged.

```php
mapError(callable $map): self
```

### Inputs:

* `$map`: callback that receives the failure value and metadata

### Behavior:

- runs only on `Fail`
- preserves metadata

Use it to normalize failure payloads before handling them further.

Use:

```php
$result = Result::fail('timeout')
    ->mapError(fn (string $error) => ['code' => 'TIMEOUT', 'message' => $error]);
```

## otherwise

`otherwise(...)` runs only on failure.

```php
otherwise(callable|object|array $next): self
```

### Inputs:

* `$next`: callback, action object, or array of steps to run on failure

### Behavior:

- a plain return value recovers to success
- a returned `Result` is propagated
- a success branch passes through unchanged

Use it when the failure path should stay inside the Result flow.

Use:

```php
$result = Result::fail('timeout')
    ->otherwise(fn ($error, array $meta) => Result::ok('retry-later', $meta));
```

## catchException

`catchException(...)` handles failure values that are `Throwable` instances by class.

```php
catchException(array $handlers, ?callable $fallback = null): self
```

### Inputs:

* `$handlers`: map of Throwable class to handler callback
* `$fallback`: optional callback for unmatched failures

### Behavior:

- only `Throwable` failures are class-matched
- handlers may return a `Result` or a plain value
- a fallback handles unmatched failures when provided
- unmatched failures pass through unchanged when no fallback exists

Use it when exception type determines the next branch.

Use:

```php
$result = Result::fail(new InvalidArgumentException('bad input'))
    ->catchException([
        InvalidArgumentException::class => fn ($error) => Result::fail('normalized'),
    ]);
```

## recover

`recover(...)` converts any failure into success.

```php
recover(callable $fn): self
```

### Inputs:

* `$fn`: callback that receives the failure value and metadata

### Behavior:

- on `Ok`, the original success is preserved
- on `Fail`, the callback produces the success value

Use it only when you intend to stop carrying the failure branch forward.

Use:

```php
$result = Result::fail('timeout')
    ->recover(fn ($error, array $meta) => 'fallback');
```

## then

`then(...)` runs a next step only on success and catches exceptions.

```php
then(callable|object|array $next): self
```

### Inputs:

* `$next`: callback, invokable object, action object, or array of steps to run on success

### Behavior:

- a step may return a plain value or a `Result`
- thrown exceptions become failure
- arrays of steps are run in order
- callable arrays such as `[$service, 'handle']` stay one step
- objects with `__invoke`, `handle`, or `execute` are supported

Use it for the default success-path pipeline.

Use:

```php
$result = Result::ok(['total' => 42])
    ->then(fn (array $order) => Result::ok([...$order, 'tax' => 4.2]));
```

## flatMap

`flatMap(...)` is an alias of `then(...)`.

```php
flatMap(callable $fn): self
```

Use it if you prefer monadic naming.

Use:

```php
$result = Result::ok(['total' => 42])
    ->flatMap(fn (array $order) => Result::ok([...$order, 'tax' => 4.2]));
```

## thenUnsafe

`thenUnsafe(...)` runs a next step only on success, but does not catch exceptions.

```php
thenUnsafe(callable|object $next): self
```

### Inputs:

* `$next`: callback or object to invoke on success

### Behavior:

- plain return values are wrapped as success
- `Result` return values are propagated
- thrown exceptions bubble up

Use it when exception bubbling is the desired boundary behavior.

Use:

```php
$result = Result::ok(['total' => 42])
    ->thenUnsafe(fn (array $order) => [...$order, 'tax' => 4.2]);
```

## See Also

- [Chaining reference](/reference/chaining)
- [Transaction rollback guide](/guides/transaction-rollback)
- [Kitchen sink overview](./)
