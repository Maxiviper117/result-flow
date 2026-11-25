# FAQ

## Is a null payload a failure?

No. `ok(null)` and `fail(null)` have different tags. Inspect with `isOk` / `isFail` or consume with `match`.

## Do chain callbacks catch exceptions?

No. Use `attempt` with an explicit Throwable class when capture is appropriate. `Retry` and `ResourceScope` are separate compatibility helpers and do capture exceptions.

## Does an empty child metadata bag clear the parent?

No. Missing keys do not delete; use `withMeta([])` on the output to replace the bag.

## Can I keep the v1 HTTP response?

`Laravel\LegacyResponse::fromResult` preserves its status and shape, including metadata. Review whether that exposure is appropriate before using it. See [boundaries](/reference/boundaries).
