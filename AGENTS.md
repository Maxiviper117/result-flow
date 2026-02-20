# AGENTS.md

## Scope and compatibility
- Target PHP 8.2+ and keep public APIs under the `Maxiviper117\ResultFlow` namespace in `src/`.
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
- Static constructor APIs (`ok`, `fail`, `failWithValue`, `of`, `defer`, `retry`, `retryDefer`, `retrier`, `bracket`) must stay documented in `docs/api.md` and reflected in Boost assets.
- Internal helpers are organized under `src/Support/Traits/`, `src/Support/Operations/`, and `src/Support/Output/`.
- `src/Support/Traits/` contains focused `Result` behavior traits (e.g., transform, unwrap, matching, taps, metadata ops).
- `src/Support/Operations/` contains operation-style services/builders (e.g., retry, pipeline, batch mapping).
- `src/Support/Output/` contains debug and serialization output helpers.
- Keep `src/Support/*` class names concise by capability (`Retry`, `Pipeline`, `Batch`, `Debug`, `Serialization`, etc.); avoid the legacy `Result*` helper naming pattern.
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
- Static analysis: `composer analyse` (or `composer phpstan`)
- Refactoring check: `composer rector-dry` (apply fixes with `composer rector`)
- Tests: `composer test` (or `composer test-coverage` when coverage is required)

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
- Ensure the guide and README stay consistent with the public API surface.
- Keep Boost guidelines and skills (`resources/boost/`) aligned with current project behavior and conventions on every change.

## Laravel Boost AI asset maintenance
- These files are consumed by Laravel Boost and are part of the package contract for AI-assisted development.
- Official Boost docs: https://laravel.com/docs/12.x/boost
- Keep `resources/boost/guidelines/core.blade.php` aligned with current public APIs, preferred patterns, and anti-patterns.
- Keep each `resources/boost/skills/*/SKILL.md` aligned with current method names and supported workflows from `src/Result.php` and Laravel integration.
- Any change to APIs, chaining behavior, metadata semantics, error shape conventions, docs examples, or Laravel integration must trigger a Boost asset review and update.
- Do not leave stale API references in guidelines or skills; remove or replace outdated examples in the same PR.
- Ensure README Boost instructions stay consistent with shipped Boost asset paths.

## Release hygiene
- Releases update `CHANGELOG.md` via GitHub workflows.
- Avoid changing package version metadata in PRs.
