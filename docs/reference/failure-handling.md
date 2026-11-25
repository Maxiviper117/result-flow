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
