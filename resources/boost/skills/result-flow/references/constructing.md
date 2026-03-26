# Constructing Reference

Use when choosing entrypoint and aggregation semantics.

## Decision table

| Need                                                                | Method          |
| ------------------------------------------------------------------- | --------------- |
| Explicit success/failure branch                                     | `ok` / `fail`   |
| Preserve failed input                                               | `failWithValue` |
| Wrap throwing callback and always wrap the return value as success  | `of`            |
| Callback may return value or `Result` and should preserve `Result`  | `defer`         |
| Aggregate existing `Result[]` fail-fast                             | `combine`       |
| Aggregate existing `Result[]` collect-all (no successes on failure) | `combineAll`    |

## Guidance

- Use the kitchen-sink construction page when you want the grouped walkthrough of entry points before choosing between `ok`, `fail`, `defer`, `retry`, and `bracket`.
- Initialize metadata early (`request_id`, `operation`).
- Prefer `of` when the callback only returns a value or throws; returned `Result` values become nested success payloads.
- Prefer `defer` when the callback can already return `Result`; returned `Result` values stay as the active branch.
- Choose fail-fast vs collect-all based on consumer requirements; `combineAll` returns only the collected failures when any input fails.

## Anti-patterns

- Using `combine` when full error set is required.
- Dropping metadata at flow start.

## Example shape

```php
$plain = Result::of(fn () => riskyFetch($input));
$flexible = Result::defer(fn () => maybeResult($input));
```

```php
$nested = Result::of(fn () => Result::fail('boom'));
$preserved = Result::defer(fn () => Result::fail('boom'));

$nested->isOk();      // true
$preserved->isFail(); // true
```
