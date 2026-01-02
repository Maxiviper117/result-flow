Issue #10: Add support for structured error types and tagged error variants

URL: https://github.com/Maxiviper117/result-flow/issues/10
Author: Maxiviper117
Updated: 2025-12-03T17:41:05Z
Labels: enhancement

Body

Result Flow currently accepts any value as the error payload. This is flexible, but it makes it harder to pattern-match on error types or enforce domain-level error structures.

Introduce optional support for structured error types. Possible approaches:

* Define a `ResultError` interface with `code()` and `message()` methods.
* Provide a `TaggedError` helper class with a stable string code and payload.
* Allow `match()` to branch on error codes or instance types.

Benefits:

* Stronger static analysis of error cases.
* Clearer distinction between different failure modes.
* More predictable HTTP/API responses when mapping errors to JSON.
* Better interoperability in domain-driven design.

This should remain optional and not break existing error payloads.

Validity

Status: valid
Reason: Current errors are `mixed`; only `matchException` handles Throwable types.

Potential change areas

- src/Result.php
- tests/
- instructions/result-guide.md
- README.md

Conclusion

`Result` currently treats error payloads as `mixed` and only has `matchException` for Throwable types. Adding structured error types would be new; keep it optional and backwards compatible, possibly via helper interfaces/classes and an enhanced matcher.
