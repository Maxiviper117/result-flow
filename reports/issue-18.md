Issue #18: Add async interoperability guidance or future-proofing

URL: https://github.com/Maxiviper117/result-flow/issues/18
Author: Maxiviper117
Updated: 2025-12-03T17:41:22Z
Labels: enhancement

Body

As PHP increasingly supports async patterns (e.g. ReactPHP, Swoole, Fibers), Result Flow could prepare for eventual interop without fully implementing async features today.

Add documentation or light helpers showing:

* How to wrap promise-like objects into Result.
* How to use Result inside async loops or parallel operations.
* How to create async-safe versions of `then()` using Fibers.

This is mainly documentation with minimal code changes, keeping the library future-ready.

Validity

Status: valid
Reason: Current code has no async-specific helpers, so this remains doc-only unless new APIs are added.

Potential change areas

- src/Result.php
- tests/
- instructions/result-guide.md
- README.md

Conclusion

This aligns with current code since there are no async-aware APIs. Keep it documentation-focused in `instructions/result-guide.md`/`README.md` showing how to wrap promise-like results into `Result::ok()`/`Result::fail()` or `Result::of()`.
