# AGENTS.md

## Scope and compatibility
- Target PHP 8.2+ and keep public APIs under the `Maxiviper117\ResultFlow` namespace in `src/`.
- Preserve type-safety and PHPStan-friendly templates when changing generics or method signatures.
- Keep behavior deterministic: no network access, randomness, or IO in core `Result` flows unless explicitly intended.

## Where to change things
- Core behavior lives in `src/` (primary class: `src/Result.php`).
- Tests live in `tests/` (Pest). Add/update tests for any behavior change.
- Docs live in `instructions/result-guide.md`; update `README.md` for user-facing changes or new public APIs.
- Laravel config integration uses `config/result-flow.php` and `src/Laravel/`.

## Development workflow
- Use a feature branch off `main`.
- Keep commits scoped and descriptive; avoid version bumps in PRs.
- Do not push git tags—maintainers handle releases and tagging.
- Do not manually edit `CHANGELOG.md` except for formatting fixes.

## Quality checks
- Format: `composer format`
- Static analysis: `composer analyse` (or `composer phpstan`)
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

## Release hygiene
- Releases update `CHANGELOG.md` via GitHub workflows.
- Avoid changing package version metadata in PRs.
