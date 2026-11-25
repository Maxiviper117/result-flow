# Chaining

Callbacks receive one active payload. They run once on the applicable branch, do not catch exceptions, and leave a skipped Result unchanged. See [validate then persist](/guides/validate-then-persist).

## `map(callable $map): Result`

`(T -> U) -> Result<U, E>`. On Ok, invokes the callback and wraps its return without flattening. On Fail, skips it. Metadata is preserved.

```php
$result = Result::ok(2)->map(fn (int $n): int => $n * 2); // Ok(4)
```

Related: [chaining concepts](/concepts/chaining).

## `andThen(callable $fn): Result`

`(T -> Result<U, F>) -> Result<U, E|F>`. On Ok, requires the callback to return a Result; on Fail, skips it. Any other return raises `TypeError`. Metadata is shallow-merged with child keys winning.

```php
$result = Result::ok(2)->andThen(fn (int $n): Result => Result::ok($n * 2));
```

Related: [validate then persist](/guides/validate-then-persist).
