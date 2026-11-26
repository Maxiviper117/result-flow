# AGENTS.md

## Scope and compatibility
- Target PHP 8.4+ and keep public APIs under the `Maxiviper117\ResultFlow` namespace in `src/`.
- CI test coverage includes PHP 8.5 and 8.4 in the main test matrix.
- Preserve type-safety and PHPStan-friendly templates when changing generics or method signatures.
- Keep behavior deterministic: no network access, randomness, or IO in core `Result` flows unless explicitly intended.

## Mandatory Maintenance Rule

- When any change affects architecture, behavior, API contracts, dependencies, scripts, setup, or developer workflow, update `AGENTS.md` in the same change set.
- If a change does not require an `AGENTS.md` update, explicitly verify that this file still matches the current project state before finishing.
- When any project change is made, always review and update Laravel Boost assets in `resources/boost/guidelines/` and `resources/boost/skills/` in the same change set so AI guidance and skills remain current.

## Knowledge freshness
- Base model knowledge may be out of date. When decisions depend on external facts, tool behavior, or versioned docs that could change, use the web search tool to confirm current information.
- For programming library/framework usage, use web search when helpful, but prefer Context7 to pull up-to-date primary docs directly from repositories before changing code or docs.

## Where to change things
- Core behavior lives in `src/` (primary class: `src/Result.php`).
- Public docs now live in `docs/` as a VitePress site with `concepts/`, `guides/`, `recipes/`, `reference/`, `kitchen-sink/`, `getting-started.md`, `faq.md`, and `laravel-boost.md`.
- The core API is `ok`, `fail`, `attempt`, `map`, `mapError`, `andThen`, `orElse`, `match`, `isOk`, `isFail`, `unwrap`, `unwrapOr`, `meta`, and `withMeta`. Keep docs and Boost assets aligned.
- Output implementation helpers live under `src/Support/Output/`; public adapters live under `src/Output/` and `src/Laravel/`.
- Structured error helpers live under `src/Support/Errors/`; use explicit PHP type checks or matching for class-specific handling.
- `src/Support/Output/` contains debug and serialization output helpers.
- Tests live in `tests/` (Pest). Add/update tests for any behavior change.
- Docs live in `docs/`; update `README.md` for user-facing changes or new public APIs.
- Laravel config integration uses `config/result-flow.php` and `src/Laravel/`.
- Laravel Boost package assets live in `resources/boost/guidelines/` and `resources/boost/skills/`.

## Development workflow
- Use a feature branch off `main`.
- Keep commits scoped and descriptive; avoid version bumps in PRs.
- Do not push git tags—maintainers handle releases and tagging.
- Do not manually edit `CHANGELOG.md` except for formatting fixes.

## Quality checks
- Format: `composer format`
- Format check only (dry-run): `composer pint-test`
- Static analysis: `composer analyse` (or `composer phpstan`); check consumer type contracts with `composer analyse:types`
- Refactoring check: `composer rector-dry` (apply fixes with `composer rector`)
- Preserve public named constructor arguments when Rector suggests constructor promotion.
- Tests: `composer test` (or `composer test-coverage` when coverage is required)
- Benchmarks: `composer bench` (phpbench callback dispatch benchmarks)
- Documentation dependencies: keep the pinned VitePress toolchain current with security fixes.
- Documentation checks: run `pnpm install --frozen-lockfile` and `pnpm docs:build` after dependency updates.

## Python usage
- Do not use Python to check files, verify contents, or perform any file validation tasks.
- Stick to agent tooling available in your environment.

## Testing guidance
- Prefer small, explicit test cases that cover both success and failure paths.
- When changing chaining behavior, include tests for metadata propagation.
- If behavior touches debug sanitization, add tests for redaction and truncation.

## Documentation expectations
- Keep examples minimal and type-safe.
- When adding a new method, include: signature, behavior, and a short example.
- Ensure the concept, guide, recipe, reference, kitchen-sink, and README pages stay consistent with the public API surface.
- Kitchen-sink docs should be organized as a section hub plus category pages, with each category covering the relevant methods in a scannable order.
- Keep boundary serialization docs aligned with implementation details such as fallback response shape and encoding failure behavior.
- Keep XML boundary docs aligned with the actual element-name normalization rules used by `toXml()`.
- Keep metadata and diagnostic documentation aligned with emitted keys.
- Keep debug sanitization docs aligned with config keys under `result-flow.debug`, including `sanitize_objects` and `object_max_depth`.
- Keep Boost guidelines and skills (`resources/boost/`) aligned with current project behavior and conventions on every change.

## Laravel Boost AI asset maintenance
- These files are consumed by Laravel Boost and are part of the package contract for AI-assisted development.
- Official Boost docs: https://laravel.com/docs/12.x/boost
- Boost guideline/skill content must be written for downstream app teams using this package, not for maintainers developing this package.
- Do not embed package-maintainer tooling/process instructions in shipped Boost assets.
- Shipped `SKILL.md` files must follow the Agent Skills spec frontmatter contract (required YAML fields such as `name` and `description`).
- Prefer a central skill with local `references/` docs and progressive disclosure over many duplicated isolated skills.
- Do not reintroduce fragmented overlapping skills unless there is a clear, documented justification.
- Keep `resources/boost/guidelines/core.blade.php` aligned with current public APIs, preferred patterns, and anti-patterns.
- Keep each `resources/boost/skills/*/SKILL.md` aligned with current method names and supported workflows from `src/Result.php` and Laravel integration.
- Any change to APIs, chaining behavior, metadata semantics, error shape conventions, docs examples, or Laravel integration must trigger a Boost asset review and update.
- Do not leave stale API references in guidelines or skills; remove or replace outdated examples in the same PR.
- Ensure README Boost instructions stay consistent with shipped Boost asset paths.

## Release hygiene
- Releases update `CHANGELOG.md` via GitHub workflows.
- Avoid changing package version metadata in PRs.
- Use a Conventional Commit title with `!` for a breaking PR so its default squash commit triggers the next major release. The v2 quickstart uses a checkout before publication and a stable `^2.0` constraint after publication.

## V2 transition
- V2 exposes a 14-method core and standalone helpers. Chaining merges metadata shallowly with the returned result taking precedence.
- On this branch `map`, `mapError`, and `match` callbacks receive only the active payload; `unwrap()` retains non-Throwable errors in `UnwrapFailure::$failure`.
- `ResultBatch` provides keyed evaluated-result aggregation and lazy mapped aggregation; metadata merges shallowly in iteration order and all-errors operations omit partial successes.
- The supported PHP floor is 8.4. `illuminate/database` is a development-only dependency for real SQLite transaction integration tests; the core runtime still requires only PHP. Composer's lock file is intentionally ignored for this library.
- The shared Pest setup resets the debug config stub before every test, including serial randomized CI runs; preserve that isolation when adding tests.
- CI runs consumer PHPStan fixtures and a `--no-dev` standalone smoke test on both supported PHP versions; `scripts/standalone-smoke.php` must remain framework-independent.
- Resource scope tests cover release exactly once when use throws, including preservation of both use and release exception identities.
- `andThen` and `orElse` enforce a real `Result` return at runtime for untyped callers; invalid returns raise `TypeError`.
- The standalone smoke script exercises success, null failure short-circuiting, recovery metadata, and propagation of an unselected exception without framework dependencies.
- Development tooling uses Pest 5, PHPStan 2.3.7, Rector 2.9.2, Laravel database 12 or 13, and the pinned VitePress 2.0.0-alpha.21 with pnpm 12; verify both lowest and stable dependency resolution on supported PHP versions.
- PHPBench 1.7 currently requires the abandoned development-only `doctrine/annotations` package; Composer audit reports this separately from security advisories.
- The built-in debug sanitizer detects recursive array references and limits nested arrays independently with `array_max_depth` (default 32, clamped 1–128); disabled/custom sanitizers bypass the built-in limit. XML requires SimpleXML and rejects invalid UTF-8 or XML 1.0 text.
- Debug key patterns span line breaks. For valid UTF-8, the sanitizer truncates at code point boundaries without requiring mbstring. Preserve coverage for multiline keys and JSON encoding without mbstring or Composer polyfills.
- Retry uses a retry-all default; document transient predicates, blocking delays and idempotency, and test thrown retry policy callbacks.
- `phpstan-result-flow.neon` enables the optional discarded-Result rule; `composer analyse:types` checks positive, negative, and explicit-suppression fixtures. The rule is static analysis only and is not a runtime dependency.
- `ResultBatch::firstSuccess` rejects empty input and preserves metadata from visited results; `zip` combines two already-evaluated Results. Field-level validation can aggregate arrays of messages with `mapAllErrors`.
- Debug output exposes structured error causes under `error_cause`, passed through the configured sanitizer; preserve redaction and truncation tests for nested cause metadata.
- `Output\ProblemDetails::response` creates an explicit RFC 9457 JSON response without automatically exposing Result payloads or metadata; test status, reserved-field collisions, encoding errors, and diagnostic privacy.

- `ResourceScope::run` releases resources after use, including on failure. `Retry::config()` captures exceptions and defaults to retrying all failures.
- `Output\Serialization` includes metadata; map public HTTP responses explicitly. Runnable examples live in `examples/v2/`.
- The documentation preview uses the normal site base; maintainers control releases and tags.
- The getting-started tutorial includes the runnable source from `examples/v2/action.php`. Verify the documented command with both a name and whitespace after changing the example.
- Keep the method finder organized by the caller's starting point. Keep the reference index mapped to the public core methods and helper pages.

- XML serialization rejects recursive arrays and arrays at 128 nesting levels; JSON rejects `JSON_PARTIAL_OUTPUT_ON_ERROR`. Debug cause expansion is bounded to 32 levels and 256 nodes before any sanitizer. The discarded-Result rule ignores never-returning calls.

- Structured cause serialization shares the debug traversal limits (32 levels, 256 visited nodes) and throws `LengthException` when exceeded; debug truncates before sanitization. Retry callback types preserve raw and Result success values and Result failures, adding `Throwable` for captured exceptions.

- Retry delay arithmetic is integer-only and rejects maximum possible waits including jitter above 2,147,483,647 ms with `OverflowException` before the retry hook; sleep uses whole seconds and a subsecond remainder. ResourceScope preserves typed raw/Result callback unions. ProblemDetails rejects 1xx, 204, 205, and 304 because it always emits a body.
