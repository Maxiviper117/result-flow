Issue #8: Add glob pattern matching for sensitive keys

URL: https://github.com/Maxiviper117/result-flow/issues/8
Author: Maxiviper117
Updated: 2025-12-01T09:36:21Z
Labels: enhancement

Body

### Summary

Add glob pattern matching for sensitive keys

### Use case

## Plan: Add glob pattern matching for sensitive keys

TL;DR: Introduce glob semantics by normalizing configured sensitive patterns to compiled case-insensitive regexes (treat plain words as implicit '*word*' to preserve existing substring behavior). Centralize matching in a helper called from `Result::defaultSanitizer()`, add tests for glob cases, and update docs/config comments.




### Proposed solution

### Steps
1. Add matcher helper in Result.php — `private static function matchesSensitiveKey(string $key, array $patterns): bool`.  
2. Precompile patterns-to-regex (cached) inside that helper; treat patterns without wildcards as `*pattern*` and support `*`/`?` semantics, case-insensitive.  
3. Replace current substring loop in `Result::defaultSanitizer()` with `matchesSensitiveKey()` (preserve non-string key handling).  
4. Add unit tests in ResultTest.php covering glob cases (`*token`, `api_*`, `?id`), case-insensitivity, numeric keys, and backward compatibility for plain words.  
5. Update result-flow.php comment and result-guide.md/README.md to document glob semantics and examples.  
6. Run tests and iterate until green; update build artifacts if necessary.



### Alternatives considered

_No response_

### Additional notes

### Further Considerations
1. Avoid `fnmatch()` for portability; use a small glob→regex converter + `preg_match`.  
2. Backwards compatibility: implicit wildcards for plain patterns preserves current behavior; consider optional exact-match flag later.  
3. Performance: cache compiled regexes and short-circuit on first match; for large pattern lists consider combined regex strategies.

Validity

Status: valid
Reason: Current sanitizer only does substring matching on lowercased keys; no glob support.

Potential change areas

- src/Result.php
- tests/
- instructions/result-guide.md
- README.md

Conclusion

Current `defaultSanitizer()` only checks `str_contains()` on lowercased keys. Adding glob support is a clear improvement; preserve existing substring behavior for plain patterns as suggested.
