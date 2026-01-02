Issue #12: Add validation result support (multiple errors, field-level issues)

URL: https://github.com/Maxiviper117/result-flow/issues/12
Author: Maxiviper117
Updated: 2025-12-03T17:41:19Z
Labels: enhancement

Body

Many workflows require collecting multiple validation errors rather than failing on the first issue. Result Flow currently supports fail-fast behavior, but lacks a way to aggregate multiple validation failures under one result.

Feature options:

* Introduce a `ValidationResult` type that stores an array of validation messages or field errors.
* Add helper methods such as `Result::fromValidator()` for framework validators.
* Allow `Result::fail()` to accept an array of errors and treat this as a standard pattern.

Benefits:

* Cleaner input validation for HTTP controllers.
* Better error reporting in domain logic workflows.
* Supports batch and field-level validation scenarios.

This should complement the existing fail-fast Result, not replace it.

Validity

Status: valid-with-design
Reason: Current `Result` only supports single error payloads and no validation-specific type.

Potential change areas

- src/Result.php
- tests/
- instructions/result-guide.md
- README.md

Conclusion

This would add new surface area beyond current `Result::fail()` behavior. Prefer a dedicated `ValidationResult` (or structured error type) to keep templates clear and avoid treating all error arrays as validation errors.
