# FAQ

## Is a null payload a failure?

No. `ok(null)` and `fail(null)` have different tags. Inspect with `isOk` / `isFail` or consume with `match`.

## Do chain callbacks catch exceptions?

No. Use `attempt` with an explicit Throwable class when capture is appropriate. `Retry` and `ResourceScope` are separate helpers and do capture exceptions.

## Does an empty child metadata bag clear the parent?

No. Missing keys do not delete; use `withMeta([])` on the output to replace the bag.

## How do I create an HTTP response?

Map the Result to your public response explicitly. See [boundaries](/reference/boundaries).

## Why did my save run even though validation failed?

Check where the save function is called. `andThen($save)` invokes it only on success. An earlier call to `$save($input)` has already performed the work before the chain can select a branch. See [chaining](/concepts/chaining).

## Why does my failed lookup look successful?

A callback returning `Result::fail(...)` inside `map` produces a successful outer Result containing the failed inner Result. Use `andThen` when the lookup should decide the next branch. The [worked lookup example](/concepts/chaining) shows both outcomes.
