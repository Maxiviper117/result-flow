Issue #11: Add combinators (all, collectErrors, firstSuccess, zip) for multi-result workflows

URL: https://github.com/Maxiviper117/result-flow/issues/11
Author: Maxiviper117
Updated: 2025-12-03T17:41:19Z
Labels: enhancement

Body

Real world workflows often involve multiple independent operations where each returns a `Result`. Result Flow currently provides `combine`, but additional helpers would improve ergonomics and reduce boilerplate.

Proposed additions:

* `Result::all(array $results)`
  Return success if all are success. Return failure on the first failure.

* `Result::collectErrors(array $results)`
  Always return a failure containing an array of all error payloads.

* `Result::firstSuccess(array $results)`
  Return the first successful result, or the last failure if all fail.

* `Result::zip(Result $a, Result $b)`
  Combine two results into a single success pair if both succeed.

Use cases include validation, batch operations, fallback strategies, and multi-step domain workflows.

Validity

Status: partial
Reason: `combine` and `combineAll` already exist; other combinators are missing.

Potential change areas

- src/Result.php
- tests/
- instructions/result-guide.md
- README.md

Conclusion

Current code already provides `combine` (short-circuit) and `combineAll` (collect errors). The remaining combinators (`all`, `collectErrors`, `firstSuccess`, `zip`) would be additive; ensure semantics differ from existing methods and keep meta merging consistent.
