Issue #15: Add tap/peek/mapSuccess/mapError helpers for side-effect ergonomics

URL: https://github.com/Maxiviper117/result-flow/issues/15
Author: Maxiviper117
Updated: 2025-12-03T17:41:21Z
Labels: enhancement

Body

Result Flow has `then()`, `otherwise()`, and `ensure()`, but no dedicated helpers for non-transforming side effects.

Introduce:

* `tap(callable)`: run a callback on success without changing value.
* `tapError(callable)`: run a callback on failure.
* `mapSuccess(callable)`: transform only the success value.
* `mapError(callable)`: transform only the error payload.

These helpers improve readability and reduce boilerplate when logging, debugging, or running small metrics steps inside pipelines.

Validity

Status: partially-implemented
Reason: `tap`, `onSuccess`/`inspect`, `onFailure`/`inspectError`, `map`, and `mapError` already exist.

Potential change areas

- src/Result.php
- tests/
- instructions/result-guide.md
- README.md

Conclusion

Most of this is already present: `tap` (both branches), `onSuccess`/`inspect`, `onFailure`/`inspectError`, `map` (success), and `mapError` (failure). Only alias names like `tapError`/`mapSuccess`/`peek` would be new, so the conclusion is to document existing methods or add aliases if needed.
