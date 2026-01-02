Issue #14: Add JSON and problem-response helpers for Result objects

URL: https://github.com/Maxiviper117/result-flow/issues/14
Author: Maxiviper117
Updated: 2025-12-03T17:41:20Z
Labels: enhancement

Body

API consumers often need a predictable error format. Result Flow requires manual `match()` handling for API responses, which works but can be verbose.

Add optional utilities such as:

* `toArray()`: convert success or failure into a simple array.
* `toJson()`: return a JSON string representing success or failure.
* `toProblemJson()`: output RFC 7807 style problem response.
* Laravel integration: optional macro for mapping results to JSON responses.

This improves DX for API development without changing core behavior.

Validity

Status: partial
Reason: `toArray()` and `toDebugArray()` already exist; JSON/problem helpers do not.

Potential change areas

- src/Result.php
- tests/
- instructions/result-guide.md
- README.md

Conclusion

`Result::toArray()` and `toDebugArray()` already cover basic serialization. New work would be `toJson()`/`toProblemJson()` plus Laravel response helpers. Keep any JSON/problem response features optional or Laravel-scoped to avoid bloating the core API.
