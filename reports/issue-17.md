Issue #17: Add retry helpers for transient failures (optional utility)

URL: https://github.com/Maxiviper117/result-flow/issues/17
Author: Maxiviper117
Updated: 2025-12-03T17:41:22Z
Labels: enhancement

Body

Some failures are transient, especially when calling external services. Users often write boilerplate retry loops manually.

Add optional retry utilities:

* `Result::retry(int $times, callable $fn)`
* `Result::retryWhen(callable $shouldRetry)`
* Support for delays or exponential backoff.

These would wrap operations in a safe retry mechanism returning a final Result.

This feature should be in a separate namespace so it does not complicate the core API.

Validity

Status: valid-with-constraints
Reason: No retry utilities exist in `Result.php`; adding them must avoid built-in timing/IO.

Potential change areas

- src/Result.php
- tests/
- instructions/result-guide.md
- README.md

Conclusion

This is reasonable if implemented outside core `Result` (e.g., separate namespace) and if any delay/backoff is user-supplied to avoid built-in `sleep()` or IO. Current `Result::of()` already wraps exceptions, so retry helpers can compose around it.
