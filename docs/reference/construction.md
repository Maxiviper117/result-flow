# Construction

All constructors run immediately. See [the Result model](/concepts/result-model) and [getting started](/getting-started).

## `ok(mixed $value, array $meta = []): Result`

Returns `Result<T, never>` with an explicit success tag. Null and falsy values remain success values. Metadata is stored as the initial shallow bag.

```php
$result = Result::ok(null, ['trace_id' => 't-1']);
// isOk() is true.
```

Related: [validate then persist](/guides/validate-then-persist).

## `fail(mixed $error, array $meta = []): Result`

Returns `Result<never, E>` with an explicit failure tag. Domain errors remain unchanged; they are not wrapped in exceptions. Null is a valid error.

```php
$result = Result::fail('Not found', ['trace_id' => 't-1']);
```

Related: [failure handling](/concepts/failure-handling).

## `attempt(callable $fn, string $catch): Result`

Calls the zero-argument callback once and wraps its return in Ok without flattening; a selected Throwable class or subclass becomes Fail. Other thrown objects implementing Throwable propagate unchanged. The required `catch` argument is a `class-string` of a Throwable type. The initial metadata bag is empty.

```php
$result = Result::attempt(
    fn () => json_decode('{"id": 1}', associative: true, flags: JSON_THROW_ON_ERROR),
    catch: JsonException::class,
);
```

For an operation already returning a Result, use typed `try/catch` if a nested Result is unwanted. Related: [failure handling](/concepts/failure-handling).

## Decode malformed input

With `JSON_THROW_ON_ERROR`, decoding `'{'` throws a `JsonException`. Passing that operation to `attempt` with `catch: JsonException::class` produces a failure carrying the exception object. By comparison, decoding the valid JSON literal `'null'` succeeds and produces `Ok(null)`. Inspect the branch before deciding whether the input was valid.
