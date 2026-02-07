---
title: API Reference
---

# API Reference

This is a complete, method-by-method reference for `Maxiviper117\ResultFlow\Result`.
Each method includes:
- What it does
- Why it exists
- When to use it
- A short example

All callbacks receive the current value or error plus metadata, unless noted otherwise:

```php
fn ($valueOrError, array $meta) => ...
```

## Static constructors

### `Result::ok()`

**What it does:** Creates a success result with a value and optional metadata.

**Why it exists:** Standardizes success values so they can be chained consistently.

**When to use:** When you already have a valid value and want to start a Result pipeline.

```php
use Maxiviper117\ResultFlow\Result;

$ok = Result::ok(['id' => 1], ['request_id' => 'r-1']);
$ok->isOk(); // true
```

### `Result::fail()`

**What it does:** Creates a failure result with an error payload and optional metadata.

**Why it exists:** Encodes failures without throwing, so pipelines can branch cleanly.

**When to use:** When a step fails and you want to return a failure instead of an exception.

```php
$fail = Result::fail('Invalid email', ['field' => 'email']);
$fail->isFail(); // true
```

### `Result::failWithValue()`

**What it does:** Creates a failure and stores the triggering input at `meta['failed_value']`.

**Why it exists:** Makes debugging and validation easier by keeping the rejected input nearby.

**When to use:** For validation failures or rejected inputs where you need the original value.

```php
$input = ['email' => 'not-an-email'];
$invalid = Result::failWithValue('Invalid email', $input, ['source' => 'signup']);

$invalid->meta()['failed_value']; // ['email' => 'not-an-email']
```

### `Result::of()`

**What it does:** Executes a callable and converts thrown exceptions into `Result::fail(Throwable)`.

**Why it exists:** Lets you wrap exception-based code so it fits Result pipelines.

**When to use:** When you want to capture exceptions and keep chaining without try/catch.

```php
$user = Result::of(fn () => $repo->findOrFail($id));
// success => Result::ok($value)
// exception => Result::fail(Throwable)
```

### `Result::retry()`

**What it does:** Retries an operation up to N times with optional delay and backoff.

**Why it exists:** Gives a simple entry point to retry transient failures.

**When to use:** For quick retry needs without configuring a full retrier builder.

```php
$result = Result::retry(3, fn () => $api->call(), delay: 100, exponential: true);
```

### `Result::retrier()`

**What it does:** Returns a fluent retry builder with predicates, jitter, and hooks.

**Why it exists:** Supports advanced retry policies without rewriting retry loops.

**When to use:** When you need selective retries, logging hooks, or jitter control.

```php
$result = Result::retrier()
    ->maxAttempts(5)
    ->delay(200)
    ->exponential()
    ->jitter(50)
    ->when(fn ($error, $attempt) => $error instanceof TimeoutException)
    ->onRetry(fn ($attempt, $error, $wait) => Log::warning("retry $attempt"))
    ->attempt(fn () => $service->call());

```

Additional builder helpers:

- `attachAttemptMeta(bool $enable = true)` — when enabled, the retrier adds `['retry' => ['attempts' => <int>]]` to every returned `Result` meta entry (successful or failed). Example:

```php
$result = Result::retrier()
    ->attachAttemptMeta()
    ->attempt(fn () => $service->call());

// $result->meta()['retry']['attempts'] === number of attempts performed
```
```

### `Result::combine()`

**What it does:** Combines multiple results, short-circuiting on the first failure.

**Why it exists:** Lets you aggregate multiple dependent operations with fail-fast semantics.

**When to use:** When any failure should stop the process immediately.

```php
$combined = Result::combine([
    Result::ok('a', ['step' => 1]),
    Result::ok('b', ['step' => 2]),
]);

$combined->unwrap(); // ['a', 'b']
$combined->meta()['step']; // 2 (last write wins)
```

### `Result::combineAll()`

**What it does:** Combines multiple results and collects all errors.

**Why it exists:** Lets you inspect all failures at once instead of failing fast.

**When to use:** For batch validation or reporting multiple issues together.

```php
$combined = Result::combineAll([
    Result::ok('a'),
    Result::fail('bad-1'),
    Result::fail('bad-2'),
]);

$combined->isFail(); // true
$combined->error(); // ['bad-1', 'bad-2']
```

### `Result::mapItems()`

**What it does:** Maps each input item to a `Result`, preserving input keys.

**Why it exists:** Removes manual item loops when each step naturally returns a `Result`.

**When to use:** When you want per-item outcomes (success/failure) without collapsing into one aggregate result.

```php
$mapped = Result::mapItems(
    ['a' => 1, 'b' => 2],
    fn (int $item, string $key) => $item > 1 ? Result::ok($item * 10) : Result::fail("bad-{$key}"),
);

$mapped['a']->isFail(); // true
$mapped['b']->value();  // 20
```

Behavior details:
- Callback signature: `fn ($item, $key) => Result|value`.
- If callback returns a plain value, it is wrapped as `Result::ok($value)`.
- If callback throws, that item becomes `Result::fail(Throwable)`.
- Always returns `array<key, Result<...>>` with original keys unchanged.

### `Result::mapAll()`

**What it does:** Maps items and short-circuits on the first failure.

**Why it exists:** Provides fail-fast batch processing for imports, writes, and multi-step domain actions.

**When to use:** When any single item failure should stop the batch immediately.

```php
$result = Result::mapAll(
    ['x' => 3, 'y' => 4],
    fn (int $item) => Result::ok($item + 1),
);

$result->value(); // ['x' => 4, 'y' => 5]
```

Behavior details:
- Processes items in input order and stops at the first failure.
- Returns `Result::ok(array<key, mappedValue>)` when all succeed.
- Returns `Result::fail(firstError)` on first failure.
- Merges metadata from processed item results in order (later keys overwrite earlier keys).
- On failure, `value()` is `null` and partial successes are not returned in `value()`.

### `Result::mapCollectErrors()`

**What it does:** Maps all items and collects every failure by item key.

**Why it exists:** Makes full batch validation/reporting easy without writing custom loops.

**When to use:** When you need to finish processing all items and return all errors together.

```php
$result = Result::mapCollectErrors(
    ['a' => 1, 'b' => 2, 'c' => 3],
    fn (int $item, string $key) => $item % 2 === 0 ? Result::ok($item) : Result::fail("bad-{$key}"),
);

$result->isFail();  // true
$result->error();   // ['a' => 'bad-a', 'c' => 'bad-c']
```

Behavior details:
- Processes all items (no short-circuiting).
- Returns `Result::ok(array<key, mappedValue>)` when no failures exist.
- Returns `Result::fail(array<key, error>)` when one or more items fail.
- Error keys match the original failing item keys.
- Merges metadata from all mapped item results in order (later keys overwrite earlier keys).
- On failure, `value()` is `null` and successful item values are not exposed in `value()`.

## State and access

### `isOk()` / `isFail()`

**What it does:** Checks the current branch (success or failure).

**Why it exists:** Lets you branch imperatively when you do not want `match()`.

**When to use:** In simple conditions or when integrating with code that expects booleans.

```php
$result = Result::ok('value');
$result->isOk();   // true
$result->isFail(); // false
```

### `value()`

**What it does:** Returns the success payload (or null if failed).

**Why it exists:** Allows direct access to stored values in edge cases.

**When to use:** When you already know the result is ok (or you are okay with null).

```php
Result::ok(123)->value();   // 123
Result::fail('err')->value(); // null
```

### `error()`

**What it does:** Returns the failure payload (or null if ok).

**Why it exists:** Allows direct access to stored errors in edge cases.

**When to use:** When you already know the result is failed (or you are okay with null).

```php
Result::fail('nope')->error();  // 'nope'
Result::ok('ok')->error();      // null
```

### `meta()`

**What it does:** Returns the metadata array.

**Why it exists:** Metadata holds context (trace IDs, step info, failed input).

**When to use:** For logging, tracing, or passing context to downstream handlers.

```php
$meta = Result::ok('v', ['trace' => 'abc'])->meta();
```

### `toArray()`

**What it does:** Serializes the raw Result state.

**Why it exists:** For explicit serialization when you control the destination.

**When to use:** When you need exact value/error/meta data without sanitization.

```php
$payload = Result::ok('v', ['m' => 1])->toArray();
// ['ok' => true, 'value' => 'v', 'error' => null, 'meta' => ['m' => 1]]
```

### `toDebugArray()`

**What it does:** Produces a sanitized, debug-safe structure.

**Why it exists:** Prevents logging secrets and huge payloads by default.

**When to use:** For logging, monitoring, or error reporting.

```php
$debug = Result::fail(new RuntimeException('boom'), ['token' => 'secret'])
    ->toDebugArray();

// ['ok' => false, 'value_type' => null, 'error_type' => 'RuntimeException', ...]
```

## Transformations

### `map()`

**What it does:** Transforms the success value and preserves failure as-is.

**Why it exists:** Allows safe success transformations without branching.

**When to use:** When you want to derive a new value from a successful result.

```php
$result = Result::ok(2)
    ->map(fn (int $v, array $meta) => $v * 10);

$result->unwrap(); // 20
```

### `mapError()`

**What it does:** Transforms the error payload and preserves success as-is.

**Why it exists:** Lets you normalize or wrap errors without changing success flow.

**When to use:** When you want errors to share a standard format or type.

```php
$result = Result::fail('bad')
    ->mapError(fn ($e, $meta) => new DomainException($e));

$result->error(); // DomainException
```

### `ensure()`

**What it does:** Turns a success into a failure if a predicate returns false.

**Why it exists:** Enables inline validation without breaking the chain.

**When to use:** For guard clauses or validation steps inside a pipeline.

```php
$result = Result::ok(['total' => 0])
    ->ensure(fn ($v) => $v['total'] > 0, 'Total must be positive');
```

## Chaining (success path)

### `then()`

**What it does:** Runs the next step on success. Exceptions are caught and converted to failure.

**Why it exists:** Standardizes sequential workflows that may fail or throw.

**When to use:** For most pipelines where you want safe chaining and failure capture.

```php
$result = Result::ok($payload, ['request_id' => $rid])
    ->then(fn ($v, $meta) => validate($v, $meta))
    ->then(fn ($v, $meta) => persist($v, $meta));
```

`then()` accepts:
- callables
- objects with `__invoke`, `handle`, or `execute`
- arrays of steps

```php
$result = Result::ok($payload)
    ->then([
        new SanitizeInput(),
        fn ($v, $meta) => Result::ok($v, ['step' => 'sanitized'] + $meta),
        [$service, 'handle'], // callable array treated as a single step
    ]);
```

When a step throws, `then()` returns `Result::fail($exception)` and adds `meta['failed_step']`.

### `flatMap()`

**What it does:** Alias for `then()`.

**Why it exists:** Provides a familiar monadic name for functional-style pipelines.

**When to use:** If you prefer `flatMap` naming or are migrating from similar APIs.

```php
$result = Result::ok(3)
    ->flatMap(fn ($v) => Result::ok($v + 1));
```

### `thenUnsafe()`

**What it does:** Runs the next step on success without try/catch.

**Why it exists:** Supports transactional flows where exceptions must bubble.

**When to use:** In DB transactions or code that must rollback on exceptions.

```php
$result = Result::ok($payload)
    ->thenUnsafe(fn ($v) => riskyWrite($v))
    ->throwIfFail();
```

If a step returns a non-Result value, it is wrapped as `Result::ok($value, $meta)`.

## Chaining (failure path)

### `otherwise()`

**What it does:** Runs a step when the result is failed.

**Why it exists:** Provides a symmetrical failure path to `then()`.

**When to use:** To recover, transform, or continue a failure chain.

```php
$result = Result::fail('cache-miss')
    ->otherwise(fn ($e, $meta) => $cache->get('value') ?? Result::fail($e));
```

### `catchException()`

**What it does:** Handles failures by matching Throwable subclasses.

**Why it exists:** Separates exception-based failures by type without manual if/else.

**When to use:** When you only want to handle specific exception classes.

```php
$result = Result::of(fn () => $client->call())
    ->catchException([
        ClientException::class => fn ($e, $meta) => 'fallback',
    ], fallback: fn ($error, $meta) => 'generic');
```

Handlers can return a `Result` or a raw value (raw values become `Result::ok`).

### `recover()`

**What it does:** Converts any failure into a success using a callback.

**Why it exists:** Guarantees a success value for downstream consumers.

**When to use:** When callers cannot handle failures and you have a safe fallback.

```php
$result = Result::fail('missing')
    ->recover(fn ($e, $meta) => 'default');

$result->unwrap(); // 'default'
```

## Side effects and metadata

### `tap()`

**What it does:** Runs a side-effect callback on both branches.

**Why it exists:** Enables logging/metrics without changing the Result.

**When to use:** For instrumentation or debugging.

```php
Result::ok('v')
    ->tap(fn ($value, $error, $meta) => logger()->info('result', compact('value', 'error', 'meta')));
```

### `onSuccess()`

**What it does:** Runs a side-effect callback only on success.

**Why it exists:** Keeps success-only side effects separate from failure handling.

**When to use:** For success-only logging, metrics, or auditing.

```php
Result::ok('v')
    ->onSuccess(fn ($value, $meta) => metrics()->increment('ok'));
```

### `inspect()`

**What it does:** Alias for `onSuccess()`.

**Why it exists:** Provides a more descriptive name for observation.

**When to use:** When you want a semantic name for success inspection.

```php
Result::ok('v')
    ->inspect(fn ($value, $meta) => audit('ok', $meta));
```

### `onFailure()`

**What it does:** Runs a side-effect callback only on failure.

**Why it exists:** Keeps failure-only side effects separate from success handling.

**When to use:** For error logging, alerts, or reports.

```php
Result::fail('bad')
    ->onFailure(fn ($error, $meta) => logger()->warning('fail', $meta));
```

### `inspectError()`

**What it does:** Alias for `onFailure()`.

**Why it exists:** Provides a more descriptive name for failure inspection.

**When to use:** When you want a semantic name for error observation.

```php
Result::fail('bad')
    ->inspectError(fn ($error, $meta) => report($error));
```

### `tapMeta()`

**What it does:** Runs a callback with metadata only.

**Why it exists:** Allows metadata-only instrumentation.

**When to use:** When you only care about metadata (trace IDs, step info).

```php
Result::ok('v', ['trace' => 'abc'])
    ->tapMeta(fn (array $meta) => logger()->debug('meta', $meta));
```

### `mapMeta()`

**What it does:** Replaces metadata with the callback return value.

**Why it exists:** Lets you normalize or reshape metadata for downstream steps.

**When to use:** When you need to replace metadata entirely.

```php
$result = Result::ok('v', ['a' => 1])
    ->mapMeta(fn ($meta) => ['b' => 2]);
```

### `mergeMeta()`

**What it does:** Shallow-merges additional metadata into the existing meta.

**Why it exists:** Makes it easy to add context without rebuilding metadata.

**When to use:** When you want to add trace IDs or step markers.

```php
$result = Result::ok('v', ['a' => 1])
    ->mergeMeta(['b' => 2]);

$result->meta(); // ['a' => 1, 'b' => 2]
```

## Pattern matching and unwrapping

### `match()`

**What it does:** Forces handling of both success and failure branches.

**Why it exists:** Eliminates forgetting to handle failures.

**When to use:** At boundaries where you must handle both outcomes explicitly.

```php
$out = Result::ok('v')->match(
    onSuccess: fn ($v, $meta) => strtoupper($v),
    onFailure: fn ($e, $meta) => 'ERR',
);
```

### `matchException()`

**What it does:** Matches Throwable classes when failed; non-Throwable errors go to `onUnhandled`.

**Why it exists:** Supports exception-specific recovery without manual checks.

**When to use:** When error types are Throwables and you need type-based handling.

```php
$out = Result::fail(new RuntimeException('boom'))
    ->matchException([
        RuntimeException::class => fn ($e, $meta) => 'handled',
    ], onSuccess: fn () => 'ok', onUnhandled: fn ($e, $meta) => 'fallback');
```

### `unwrap()`

**What it does:** Returns the success value or throws.

**Why it exists:** Gives a direct escape hatch for tests and boundaries.

**When to use:** When you prefer an exception on failure.

```php
Result::ok(5)->unwrap();               // 5
Result::fail(new RuntimeException)->unwrap(); // throws
Result::fail('bad')->unwrap();         // throws RuntimeException('bad')
```

### `unwrapOr()`

**What it does:** Returns the success value or a default when failed.

**Why it exists:** Avoids exceptions when a safe default exists.

**When to use:** When you need a fallback and do not want to throw.

```php
$value = Result::fail('missing')->unwrapOr('default');
```

### `unwrapOrElse()`

**What it does:** Returns the success value or computes a fallback from error and meta.

**Why it exists:** Lets you derive defaults based on failure context.

**When to use:** When fallback depends on the error or metadata.

```php
$value = Result::fail('missing', ['code' => 404])
    ->unwrapOrElse(fn ($e, $meta) => "{$e}-{$meta['code']}");
```

### `getOrThrow()`

**What it does:** Throws a custom exception built from error and meta.

**Why it exists:** Allows domain-specific exceptions at boundaries.

**When to use:** When callers expect specific exception types.

```php
$value = Result::fail('invalid')
    ->getOrThrow(fn ($e, $meta) => new InvalidArgumentException($e));
```

### `throwIfFail()`

**What it does:** Throws on failure, otherwise returns `$this` for continued chaining.

**Why it exists:** Lets you keep fluent chains while escalating failures to exceptions.

**When to use:** After `thenUnsafe()` or at transactional boundaries.

```php
$result = Result::ok('v')
    ->thenUnsafe(fn ($v) => riskyWrite($v))
    ->throwIfFail();
```

## Output transformers

### `toJson()`

**What it does:** Serializes the result to JSON with `JSON_THROW_ON_ERROR`.

**Why it exists:** Provides a consistent JSON shape for responses and logs.

**When to use:** When you need a JSON payload directly.

```php
$json = Result::ok(['id' => 1])->toJson(JSON_PRETTY_PRINT);
```

### `toXml()`

**What it does:** Serializes the result to XML.

**Why it exists:** Supports systems that require XML.

**When to use:** When integrating with legacy or XML-only interfaces.

```php
$xml = Result::ok(['a', 'b'])->toXml('api-response');
```

### `toResponse()`

**What it does:** Returns a Laravel JSON response when available, or a fallback array shape.

**Why it exists:** Makes it easy to return results from controllers and handlers.

**When to use:** At HTTP boundaries.

```php
$response = Result::fail('bad')->toResponse();
// Laravel: JsonResponse (status 400)
// No framework: ['status' => 400, 'headers' => [...], 'body' => '{...}']
```
