Issue #19: Add ability to map failure types to log levels

URL: https://github.com/Maxiviper117/result-flow/issues/19
Author: Maxiviper117
Updated: 2025-12-03T17:41:23Z
Labels: enhancement

Body

Result Flow supports debug sanitization but does not determine log levels for different error types.

Add optional configuration or helper:

* Map error codes/classes to log levels.
* Use this mapping inside `toDebugArray()` or a new `toLogContext()` helper.

This improves observability without polluting the core API.

Validity

Status: valid
Reason: Current Result has `toDebugArray()` with sanitization but no log-level mapping.

Potential change areas

- src/Result.php
- tests/
- instructions/result-guide.md
- README.md

Conclusion

This is a valid enhancement given current `toDebugArray()` only returns a sanitized payload and has no log-level logic. A `toLogContext()` helper or Laravel integration could map error types to levels while staying deterministic and IO-free.
