# Chaining

`map` transforms an Ok payload into any value, including a nested Result. `andThen` expects a Result and flattens that one step. Neither catches exceptions. Fail skips both callbacks. See the [chaining reference](/reference/chaining) and [validate then persist](/guides/validate-then-persist).

## When a callback can reject the input

Suppose a lookup returns a failed Result for a missing customer. Both chains below invoke the same lookup. The choice of method determines what the next step sees.

```php
use Maxiviper117\ResultFlow\Result;

$lookup = fn (int $id): Result => Result::fail("Missing customer $id");

$nested = Result::ok(42)->map($lookup);
$nested->isOk();           // true
$nested->unwrap()->isFail(); // true

$customer = Result::ok(42)->andThen($lookup);
$customer->isFail();       // true
```

A callback passed to `map` may produce any value, so a returned Result becomes the new success payload. With `andThen`, a missing customer becomes the failure of the whole chain. Any later success step, such as sending that customer a receipt, is skipped.

| Your callback | Continue on success | Continue on failure |
| --- | --- | --- |
| Returns an ordinary value | `map` changes the success value | `mapError` changes the error |
| Returns a Result | `andThen` runs the next action | `orElse` attempts recovery |

If the lookup throws, the exception escapes both chains. Use [selective exception capture](/reference/construction) at a boundary where you know which exceptions represent expected failures.
