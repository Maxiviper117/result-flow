Issue #13: Add error cause chaining to preserve nested failure context

URL: https://github.com/Maxiviper117/result-flow/issues/13
Author: Maxiviper117
Updated: 2025-12-03T17:41:20Z
Labels: enhancement

Body

When a pipeline calls deeper operations, failures often originate several layers below the surface. Result Flow currently stores a single error payload, but no built-in mechanism captures nested causes or error chains.

Add optional support for:

* Attaching a `cause` to a failure.
* A `Result::failWithCause($error, $cause)` helper.
* A standardized internal representation for causal chains.
* Including cause chains in `toDebugArray()`.

This improves debugging, log clarity, and understanding of failure propagation in complex pipelines.

Validity

Status: valid
Reason: `Result` has no cause chaining; only single error payload is stored today.

Potential change areas

- src/Result.php
- tests/
- instructions/result-guide.md
- README.md

Conclusion

Current `Result` only stores a single error payload. Cause chaining would be new and should remain data-only, ideally surfaced in `toDebugArray()` with sanitization and explicit typing for nested causes.
