---
title: Matching and unwrapping
---

# Matching and unwrapping

Pattern matching APIs force you to handle both branches explicitly. Unwrapping helpers provide controlled escape hatches when you need the raw value.

## Pattern matching

### Exhaustive branching with `match()`

```php
$out = Result::ok($user)->match(
    onSuccess: fn(User $u, array $meta) => view('profile', ['user' => $u]),
    onFailure: fn($error, array $meta) => view('error', ['message' => $error]),
);
```

### Exception-aware branching with `matchException()`

```php
$response = Result::of(fn() => $client->call())
    ->matchException(
        exceptionHandlers: [
            ClientException::class => fn($e, $meta) => Result::fail('4xx from upstream'),
            ServerException::class => fn($e, $meta) => Result::fail('5xx from upstream'),
        ],
        onSuccess: fn($payload, $meta) => Result::ok($payload, $meta),
        onUnhandled: fn($error, $meta) => Result::fail($error, $meta),
    );
```

The first matching class handler runs. Non-Throwables and unmatched exceptions fall back to `onUnhandled`.

## Unwrapping helpers

### `unwrap()` throws on failure

```php
try {
    $payload = Result::ok(['id' => 1])->unwrap();
} catch (Throwable $e) {
    // Never reached for ok values
}
```

If the failure contains a `Throwable`, that exception is rethrown; otherwise a `RuntimeException` is thrown with the string error message.

### Provide defaults with `unwrapOr()` and `unwrapOrElse()`

```php
$user = loadUser($id)
    ->unwrapOr(new GuestUser());

$withDynamicDefault = loadUser($id)
    ->unwrapOrElse(fn($error) => fetchFromCache($error));
```

### Throw custom exceptions with `getOrThrow()`

```php
$dto = Result::fail('invalid-state')
    ->getOrThrow(fn($error) => new DomainException($error));
```

### Re-throw failures after unsafe steps with `throwIfFail()`

```php
$result = Result::ok($input)
    ->thenUnsafe(fn($data) => writeToDisk($data))
    ->throwIfFail(); // throws the Throwable stored in the failure, otherwise returns $this
```
