---
title: Matching and unwrapping
---

# Matching and unwrapping

Pattern matching forces you to handle both branches explicitly. Unwrapping helpers are the escape hatch when you need a raw value or an exception.

Use this page when you want to consume results (convert them into values, responses, or exceptions). If you are still in a pipeline, prefer chaining methods (`then`, `otherwise`, `recover`) and unwrap at the boundary.

## Pattern matching

### Exhaustive branching with `match()`

```php
$out = Result::ok($user)->match(
    onSuccess: fn (User $u, array $meta) => view('profile', ['user' => $u]),
    onFailure: fn ($error, array $meta) => view('error', ['message' => $error]),
);
```

`match()` returns whatever your callbacks return. This makes it ideal for building HTTP responses, view models, or CLI output in a single expression.

### Exception-aware branching with `matchException()`

```php
$response = Result::of(fn () => $client->call())
    ->matchException(
        exceptionHandlers: [
            ClientException::class => fn ($e, $meta) => Result::fail('4xx from upstream'),
            ServerException::class => fn ($e, $meta) => Result::fail('5xx from upstream'),
        ],
        onSuccess: fn ($payload, $meta) => Result::ok($payload, $meta),
        onUnhandled: fn ($error, $meta) => Result::fail($error, $meta),
    );
```

Behavior summary:
- If ok: calls `onSuccess(value, meta)`.
- If failed with a Throwable and a matching class exists: calls the matching handler.
- Otherwise: calls `onUnhandled(error, meta)`.

## Unwrapping (escape hatch)

Use unwrapping when you explicitly want a value or an exception (tests, CLI commands, boundaries). If you need to handle both branches, prefer `match()`. Unwrapping throws by design so failures cannot be silently ignored.

### `unwrap()` returns the value or throws

```php
// Success => returns the value
$id = Result::ok(42)->unwrap();

// Failure with Throwable => rethrows the original exception
Result::fail(new RuntimeException('boom'))->unwrap();

// Failure with string => RuntimeException('missing')
Result::fail('missing')->unwrap();
```

If the failure is not a Throwable or string, `unwrap()` throws `RuntimeException('Result failed')`.

### `unwrapOr()` provides a default

```php
$user = Result::fail('not-found')->unwrapOr(new GuestUser());
```

### `unwrapOrElse()` computes a default from error and meta

```php
$user = Result::fail('not-found', ['source' => 'cache'])
    ->unwrapOrElse(fn ($error, $meta) => new GuestUser($meta['source']));
```

`unwrapOrElse()` is lazy: the callback only runs on failure, so you can put expensive logic there safely.

### `getOrThrow()` throws a custom exception

```php
$result = Result::fail('invalid', ['code' => 422])
    ->getOrThrow(fn ($error, $meta) => new DomainException("{$error}-{$meta['code']}") );
```

### `throwIfFail()` escalates failures in a chain

```php
$final = Result::ok($input)
    ->thenUnsafe(fn ($v) => riskyWrite($v))
    ->throwIfFail(); // throws on failure, returns $this on success
```

`throwIfFail()` uses a best-effort stringification for non-Throwable errors (JSON when possible, otherwise `var_export`). This can produce more detail than `unwrap()` for array errors.
