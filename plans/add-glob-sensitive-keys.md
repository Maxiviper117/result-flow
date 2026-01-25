# Plan: Add glob pattern matching for sensitive keys

## Summary
Add glob semantics to the sensitive-keys sanitizer so configured patterns can use `*` and `?` while preserving current substring behavior for plain words (implicit `*` on both sides). Centralize matching in a helper and update tests, config comments, and docs.

## Problem / Current state
- The default sanitizer currently checks configured sensitive keys using simple substring checks on lower-cased string keys (see `src\Support\ResultDebug.php::defaultSanitizer`).
- There is no support for glob-like patterns (e.g., `api_*`, `*token`, `?id`).

## Goal
- Allow administrators to specify glob-style patterns for `sensitive_keys` (in `config/result-flow.php`) with case-insensitive matching.
- Preserve backward compatibility: plain words should behave as before (implicit substring match).
- Implement this with a small, well-tested helper and minimal surface changes.

## Proposed approach
1. Add a centralized matcher helper (co-located with the sanitizer):
   - `private static function matchesSensitiveKey(string $key, array $patterns): bool`
   - Precompile patterns to case-insensitive regexes with caching.
   - Pattern semantics: `*` -> `.*`, `?` -> `.`, and if a pattern contains neither `*` nor `?`, treat it as `*<pattern>*` to preserve substring behavior.
   - Use `preg_match('/^...$/i', $key)` to test; avoid `fnmatch()` for portability.
2. Replace the current substring loop in `defaultSanitizer()` with a call to `matchesSensitiveKey()` (only call it for string keys — preserve numeric/non-string handling).
3. Add unit tests that cover:
   - Leading/trailing wildcard: `*token`, `token*`, `*token*`
   - Single-character wildcard: `?id` matches `xid` but not `id`
   - Prefix/suffix globs: `api_*`, `*_key`
   - Case-insensitivity: `ToKeN` matches `token`
   - Numeric and non-string keys remain unaffected
   - Backward compatibility for plain words: `password` still matches keys containing `password`
4. Update documentation/config comments:
   - `config/result-flow.php` comment for `sensitive_keys` to explain glob semantics and examples
   - `docs/result/metadata-debugging.md`, `docs/guides/internals.md`, and README references
5. Run tests and iterate until green.

## Workplan (checkboxes)
- [ ] Confirm placement of the matcher helper (ResultDebug vs Result.php).
- [ ] Implement `matchesSensitiveKey()` in agreed file (signature and behavior above).
- [ ] Implement pattern->regex conversion with caching (static cache keyed by pattern list hash).
- [ ] Replace substring loop in `defaultSanitizer()` and ensure non-string key behavior is preserved.
- [ ] Add unit tests in `tests/ResultDebugTest.php` (new cases as above).
- [ ] Update `config/result-flow.php` comment and docs (`docs/result/metadata-debugging.md`, `docs/guides/internals.md`, `README.md`).
- [ ] Run `composer test` and fix any regressions.
- [ ] (Optional) Performance review for large pattern lists and consider combined-regex optimization later.

## Files likely to change
- src\Support\ResultDebug.php
- config\result-flow.php
- tests\ResultDebugTest.php
- docs\result\metadata-debugging.md
- docs\guides\internals.md
- README.md

## Implementation notes / constraints
- Avoid `fnmatch()` for portability; implement a tiny glob->regex converter using `preg_quote()` followed by replacing escaped `\\*`/`\\?` sequences.
- Cache compiled regexes for each configured pattern list to avoid repeated compilation on recursive sanitization runs.
- Short-circuit on first match for performance.
- Preserve existing behavior for non-string keys and for plain patterns (implicit wildcard wrapping).
- Consider adding an optional exact-match flag later if users request it.

## Tests & validation
- Add focused unit tests for each glob scenario and maintain existing debug tests.
- Run `composer test` and confirm green before submitting changes.

## Estimated effort
Small change: ~1-3 hours including tests and docs updates.

---

Notes: This plan intentionally keeps the helper close to the sanitizer (in `src\Support\ResultDebug.php`) because `defaultSanitizer()` currently lives there; if you prefer the helper in `src\Result.php` or a separate utility class, confirm in the question below.
