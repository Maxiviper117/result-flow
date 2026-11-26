# Failure handling

Failure callbacks receive one error. They are skipped on Ok and do not catch exceptions. See [failure concepts](/concepts/failure-handling).

## `mapError(callable $map): Result`

`(E -> F) -> Result<T, F>`. Transforms a Fail without recovering, preserving metadata; an Ok is unchanged.

```php
$result = Result::fail('missing')->mapError(fn (string $e): string => strtoupper($e));
// Fail('MISSING')
```

Related: [API boundary mapping](/recipes/api-boundary-mapping).

## `orElse(callable $fn): Result`

`(E -> Result<U, F>) -> Result<T|U, F>`. Lazily replaces a Fail with the returned Result; any other return raises `TypeError`. The original error is discarded unless the callback explicitly retains it. Returned metadata is shallow-merged with child keys winning; an Ok is unchanged.

```php
$result = Result::fail('unavailable')->orElse(fn (string $e): Result => Result::ok('cached'));
```

Related: [validate then persist](/guides/validate-then-persist).

## Give callers a stable error code

Use a structured error when several callers need to distinguish failure reasons. A CLI can display the message while an HTTP handler switches on the code. Keep diagnostic details in the payload or cause, and choose which fields each caller may see.

```php
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\DataTaggedError;

$error = new DataTaggedError('stock.empty', 'Item is out of stock', ['sku' => 'A42']);
$result = Result::fail($error);
$status = $result->match(
    onSuccess: fn ($order): int => 201,
    onFailure: fn (DataTaggedError $e): int => $e->code() === 'stock.empty' ? 409 : 400,
);
// $status === 409
```

`code()` returns the string domain code. `message()`, `payload()`, and `cause()` expose the other parts. A tagged error is throwable, so calling `unwrap()` on this failure throws the same error object.
