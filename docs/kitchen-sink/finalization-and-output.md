---
title: Finalization and Output
---

# Finalization and Output

This group covers the functions that close a flow or turn it into transport-safe output.

## Quick Map

| Function | What it does |
| --- | --- |
| `match` | Finishes the result by handling both branches explicitly |
| `matchException` | Handles Throwable failures by class |
| `unwrap` | Returns the success value or throws the failure |
| `unwrapOr` | Returns the success value or an eager default |
| `unwrapOrElse` | Returns the success value or a lazy default |
| `getOrThrow` | Returns the success value or throws a custom exception |
| `throwIfFail` | Throws on failure and returns the same result on success |
| `toArray` | Returns the raw branch shape |
| `toDebugArray` | Returns debug-safe output |
| `toJson` | Serializes to JSON |
| `toXml` | Serializes to XML with name normalization |
| `toResponse` | Converts to an HTTP response shape |

## match

`match(...)` finishes the result by handling both branches explicitly.

Use it when the caller needs a branch-aware output value.

Shape:

```php
// mixed return value from the matching branch
```

Use:

```php
$payload = $result->match(
    onSuccess: fn ($value, array $meta) => ['ok' => true, 'data' => $value],
    onFailure: fn ($error, array $meta) => ['ok' => false, 'error' => $error],
);
```

## matchException

`matchException(...)` handles Throwable failures by class.

- successful results go to `onSuccess`
- Throwable failures go to the first matching handler
- everything else goes to `onUnhandled`

Use it when exception class determines the final output shape.

Shape:

```php
// mixed return value from the matching branch
```

Use:

```php
$message = $result->matchException(
    onSuccess: fn ($value) => "ok: {$value}",
    onFailure: [
        InvalidArgumentException::class => fn ($error) => 'bad input',
    ],
    onUnhandled: fn ($error) => 'unexpected failure',
);
```

## unwrap

`unwrap()` returns the success value or throws the failure value.

It is the strictest boundary helper, so use it only when the caller expects plain values and failure should escape immediately.

Shape:

```php
// $successValue
// or throws the failure
```

Use:

```php
$value = $result->unwrap();
```

## unwrapOr

`unwrapOr()` returns the success value or an eager fallback.

The fallback is evaluated before the call, so use it only when that default is cheap or already available.

Shape:

```php
// $successValue
// or the eager fallback
```

Use:

```php
$value = $result->unwrapOr('guest');
```

## unwrapOrElse

`unwrapOrElse()` returns the success value or computes a lazy fallback.

The callback receives the failure value and metadata, so you can derive a fallback from the failure context.

Shape:

```php
// $successValue
// or the callback result
```

Use:

```php
$value = $result->unwrapOrElse(fn ($error, array $meta) => $meta['fallback'] ?? null);
```

## getOrThrow

`getOrThrow()` returns the success value or throws a custom exception produced by the callback.

Use it when you want a domain-specific exception type at the boundary instead of the raw failure value.

Shape:

```php
// $successValue
// or throws the custom exception
```

Use:

```php
$value = $result->getOrThrow(fn ($error, array $meta) => new RuntimeException((string) $error));
```

## throwIfFail

`throwIfFail()` throws on failure and otherwise returns the same result instance.

That makes it useful when you want an exception boundary without losing the fluent chain on success.

Shape:

```php
// returns the same Result on success
// throws on failure
```

Use:

```php
$result = $result->throwIfFail();
```

These functions convert a Result into a plain value or a thrown exception.

- `unwrap()` returns the value or throws the failure
- `unwrapOr()` returns the value or an eager default
- `unwrapOrElse()` returns the value or computes a lazy default from error and metadata
- `getOrThrow()` returns the value or throws a custom exception
- `throwIfFail()` throws on failure and otherwise returns the same result so chaining can continue

Use them only when the boundary genuinely expects plain values or exceptions.

## toArray

`toArray()` returns the raw branch shape:

```php
['ok' => bool, 'value' => mixed, 'error' => mixed, 'meta' => array]
```

Use it for trusted internal serialization or inspection when redaction is not needed.

Shape:

```php
// ['ok' => bool, 'value' => mixed, 'error' => mixed, 'meta' => array]
```

Use:

```php
$payload = $result->toArray();
```

## toDebugArray

`toDebugArray(...)` returns a debug-safe shape.

- success includes `value_type`
- failure includes `error_type` and `error_message`
- metadata is sanitized
- sensitive keys are redacted and long strings are truncated by default

Use it for logs, traces, and diagnostics.

Shape:

```php
// sanitized array with types, redaction, and truncation applied
```

Use:

```php
$debug = $result->toDebugArray();
```

## toJson

`toJson(...)` serializes the raw branch shape to JSON.

- uses `JSON_THROW_ON_ERROR`
- accepts JSON encoding options

Use it when the boundary expects JSON text.

Shape:

```php
// JSON string representing the raw branch shape
```

Use:

```php
$json = $result->toJson();
```

## toXml

`toXml(...)` serializes the raw branch shape to XML.

- invalid characters become underscores
- names that cannot start as XML elements are prefixed with `item_`
- names that begin with `xml` are prefixed with `item_`
- numeric keys become `item{n}`

Use it when the boundary expects XML, but do not treat it as a lossless key mirror.

Shape:

```php
// XML string representing the raw branch shape
```

Use:

```php
$xml = $result->toXml();
```

## toResponse

`toResponse(...)` converts the result to an HTTP response.

- in Laravel, it returns a JSON response object when the response factory exists
- outside Laravel, it returns an array with `status`, `headers`, and a JSON string `body`
- status is `200` for success and `400` for failure

Use it at HTTP boundaries, not deep in domain code.

Shape:

```php
// Laravel response object or ['status' => ..., 'headers' => ..., 'body' => ...]
```

Use:

```php
$response = $result->toResponse();
```

## See Also

- [Boundary reference](/reference/boundaries)
- [Kitchen sink overview](./)
