# Examples

Run these scripts from project root to manually inspect behavior outside automated tests.

Suggested order if you are new:
1. `examples\construction\of-defer-demo.php`
2. `examples\defer\defer-test.php`
3. `examples\retry\retry-test.php`
4. `examples\retry\retry-defer-test.php`
5. `examples\defer\bracket-test.php`
6. `examples\batch\batch-map-demo.php`
7. `examples\debug\debug-sanitization-demo.php`

## Construction examples

### `php examples\construction\of-defer-demo.php`

Purpose:
- Compare `Result::of()` and `Result::defer()` in one place.

Concept:
- `Result::of()` wraps a callback that returns a plain value or throws.
- `Result::defer()` wraps a callback that may return either a plain value or another `Result`.
- Both constructors convert thrown exceptions into failure results.

## Debug examples

### `php examples\debug\debug-sanitization-demo.php`

Purpose:
- Validate debug/serialization behavior for sensitive metadata fields.

Concept:
- Builds a failing `Result` with metadata containing sensitive-like keys.
- Prints both `toArray()` and `toDebugArray()` so you can compare raw vs debug-safe output.

## Retry examples

### `php examples\retry\retry-test.php`

Purpose:
- Explore retry behavior under success, repeated failure, and conditional stop rules.

Concept:
- Runs operations through `Result::retry(...)` and `Result::retrier()`.
- Shows three scenarios:
  - throw then success
  - retrier with delay/backoff/jitter + retry callback log
  - predicate-based early stop

### `php examples\retry\retry-defer-test.php`

Purpose:
- Try `Result::retryDefer(...)` with callbacks that throw, return plain values, or return `Result`.

Concept:
- Replays an operation up to `times` using defer-style normalization each attempt.
- Shows value-return, `Result`-return, and throwable-returning callbacks under retry.

## Batch examples

### `php examples\batch\batch-map-demo.php`

Purpose:
- Demonstrate batch processing patterns for collections of items.

Concept:
- Maps input items through Result-producing callbacks.
- Prints three distinct patterns:
  - `mapItems` (per-item result map)
  - `mapAll` (fail-fast aggregate with visited keys)
  - `mapCollectErrors` (aggregate with all keyed failures)

## Deferred and resource examples

### `php examples\defer\defer-test.php`

Purpose:
- Try `Result::defer()` end-to-end with different callback outcomes.

Concept:
- Executes callbacks that return plain values, return `Result`, or throw exceptions.
- Demonstrates normalization rules (`value -> ok`, `Result -> passthrough`, `throw -> fail`) plus a chained flow with metadata.

### `php examples\defer\bracket-test.php`

Purpose:
- Explore resource-safe acquire/use/release flows with `Result::bracket(...)`.

Concept:
- Demonstrates full lifecycle outcomes:
  - acquire/use/release success
  - use fail + release success
  - use fail + release throw (`meta['bracket.release_exception']`)
  - use success + release throw (overall fail)
  - acquire fail (release skipped)

## Misc examples

### `php examples\misc\doodle.php`

Purpose:
- Scratchpad file for quick local experiments.

Concept:
- Minimal bootstrap you can edit freely for ad-hoc checks without changing the focused example scripts.
