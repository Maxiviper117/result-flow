# Boundaries and output

Use `match` for explicit response mapping. See the [API boundary recipe](/recipes/api-boundary-mapping) and [transaction guide](/guides/transaction-rollback).

## `match(callable $onSuccess, callable $onFailure): mixed`

Invokes exactly one handler with the active payload and returns its value. Its inferred return type is the union of the handlers' return types. Exceptions propagate; metadata is not passed implicitly or changed.

```php
$message = Result::fail('missing')->match(fn ($v) => "Found $v", fn ($e) => "Error: $e");
```

Related: [API boundary recipe](/recipes/api-boundary-mapping).

## `isOk(): bool`

Inspects the branch tag without interpreting its payload. It does not mutate metadata or call callbacks.

```php
$isOk = Result::ok(null)->isOk(); // true
```

Related: [Result model](/concepts/result-model).

## `isFail(): bool`

Inspects the failure tag without interpreting its payload. It does not mutate metadata or call callbacks.

```php
$isFail = Result::fail(null)->isFail(); // true
```

Related: [Result model](/concepts/result-model).

## `unwrap(): mixed`

Returns the success payload. A Throwable failure is rethrown unchanged; other failures raise `UnwrapFailure` with the original value in `failure`. Metadata is unchanged.

```php
$value = Result::ok(42)->unwrap(); // 42
```

Related: [transaction rollback](/guides/transaction-rollback).

## `unwrapOr(mixed $default): mixed`

Returns the success payload or the eager default on failure. Its inferred type is `T|U`. PHP evaluates the default before the call; use `match` for lazy fallback. Metadata is unchanged.

```php
$value = Result::fail('missing')->unwrapOr(0); // 0
```

Related: [Result model](/concepts/result-model).

## Explicit adapters

`Output\LegacyOutput::toArray`, `toJson`, and `toXml` preserve the v1 shape, including metadata. JSON encoding throws `JsonException` on failure. XML requires SimpleXML and throws `RuntimeException` when it is unavailable. Invalid UTF-8 or XML 1.0 characters raise `InvalidArgumentException` rather than silently losing text; numeric keys become `item` plus digits, invalid characters become underscores, surrounding underscores are trimmed, empty names become `item`, and names starting with a digit or `xml` get an `item_` prefix.

`Laravel\LegacyResponse::fromResult($result)` preserves the v1 HTTP status (200 for Ok, 400 for Fail) and shape. With Laravel's response helper it returns a JSON response; otherwise it returns `['status' => ..., 'headers' => ['Content-Type' => 'application/json'], 'body' => $json]`. The fallback can throw `JsonException`. These compatibility adapters include metadata, so review the existing contract and do not use them to expose diagnostics in new endpoints.
