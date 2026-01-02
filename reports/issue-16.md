Issue #16: Add collection helpers for mapping arrays to Result pipelines

URL: https://github.com/Maxiviper117/result-flow/issues/16
Author: Maxiviper117
Updated: 2025-12-03T17:41:21Z
Labels: enhancement

Body

Developers often process batches of items where each step returns a Result. It currently requires manual loops.

Add utilities such as:

* `Result::map(array $items, callable $fn)`: produce array of results.
* `Result::mapAll(array $items, callable $fn)`: stop on first failure, collect successes.
* `Result::mapCollectErrors(array $items, callable $fn)`: collect all failures.

This supports data imports, batch writes, and multi-item domain workflows.

Validity

Status: valid
Reason: `combine`/`combineAll` exist, but no per-item mapping helpers in `Result.php`.

Potential change areas

- src/Result.php
- tests/
- instructions/result-guide.md
- README.md

Conclusion

This fits alongside existing `combine`/`combineAll` but would be new APIs. Ensure consistent meta merging like `combine` and document stop-on-failure vs collect-all-errors behavior.
