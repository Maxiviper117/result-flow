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

## V2 transition
- V2 exposes a 14-method core and standalone helpers. Chaining merges metadata shallowly with the returned result taking precedence.
- On this branch `map`, `mapError`, and `match` callbacks receive only the active payload; `unwrap()` retains non-Throwable errors in `UnwrapFailure::$failure`.
- `ResultBatch` provides keyed evaluated-result aggregation and lazy mapped aggregation; metadata merges shallowly in iteration order and all-errors operations omit partial successes.
- `ResourceScope::run` preserves v1 bracket behavior, including release on use failure and `bracket.release_exception` metadata when both use and release fail.
- `Retry::config()` is the public v1-compatible retry builder; document its broad exception capture and recommend an explicit `when` predicate for transient failures.
- `Output\LegacyOutput` and `Laravel\LegacyResponse` explicitly preserve v1 wire shapes, including metadata; `Output\DebugOutput` provides sanitized diagnostics. New public responses should map domain errors and allowlist fields explicitly.
- V2 runnable examples live in `examples/v2/`; the benchmark covers core callback dispatch. The documentation sidebar and migration map describe v2, while stable v1 remains on its existing release line.
- The supported PHP floor is 8.4. `illuminate/database` is a development-only dependency for real SQLite transaction integration tests; the core runtime still requires only PHP. Composer's lock file is intentionally ignored for this library.
- The shared Pest setup resets the debug config stub before every test, including serial randomized CI runs; preserve that isolation when adding tests.
- `examples/v2/order-endpoint.php` demonstrates a migrated legacy response; its request handler and regression tests keep internal trace metadata out of the public response while preserving the prior JSON shape and status.
- Pull requests build an unpublished v2 documentation artifact with the `/result-flow/v2-preview/` base; the stable site deployment remains a separate workflow. Maintainers own prerelease tags and publication.
- CI runs consumer PHPStan fixtures and a `--no-dev` standalone smoke test on both supported PHP versions; `scripts/standalone-smoke.php` must remain framework-independent.
- Until stable v2 is released, the quickstart uses an explicit v2 beta Composer constraint so installation does not silently select v1.
- Stable GitHub Pages deployment is manually dispatched from `main`, so merging v2 work does not automatically overwrite the v1 site before the major release.
