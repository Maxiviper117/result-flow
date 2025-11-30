# Contributing to Result Flow

Thanks for wanting to help! This project is small, so the process is lightweight—keep changes focused and make sure automated checks pass.

## Getting set up
- Require: PHP 8.2+ and Composer.
- Install dependencies: `composer install`.
- Use a feature branch off `main` for your work.

## Development flow
- Add or update tests in `tests/` for any behavior change (Pest is already configured).
- For user-facing changes, add a release note in the PR description; the GitHub workflow updates `CHANGELOG.md` when a release is published (no manual edits needed unless fixing formatting).
- Keep commits scoped and descriptive; avoid version bumps in PRs.

## Quality checks
- Format: `composer format`
- Static analysis: `composer analyse`
- Tests: `composer test` (or `composer test-coverage` if you need coverage)

## Opening a pull request
- Ensure the commands above pass locally.
- Explain the motivation and any breaking changes in the PR description.
- Link related issues if they exist; small, single-topic PRs get reviewed faster.
