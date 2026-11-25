# FAQ

## Is a null payload a failure?

No. `ok(null)` and `fail(null)` have different tags. Inspect with `isOk` / `isFail` or consume with `match`.

## Do chain callbacks catch exceptions?

No. Use `attempt` with an explicit Throwable class when capture is appropriate. `Retry` and `ResourceScope` are separate helpers and do capture exceptions.

## Does an empty child metadata bag clear the parent?

No. Missing keys do not delete; use `withMeta([])` on the output to replace the bag.

## How do I create an HTTP response?

Map the Result to your public response explicitly. See [boundaries](/reference/boundaries).
