---
title: Laravel Boost
---

# Laravel Boost

## Introduction

Laravel Boost uses two package asset types:

- Guidelines define project conventions and preferred patterns so generated code follows consistent ResultFlow usage.
- Skills define task-focused workflows that help agents produce structured outputs for common jobs (for example Laravel pipelines or debugging flows).

In short: guidelines shape *how* code should look; skills shape *how* specific tasks should be executed.

## What this page is for

This page documents the Laravel Boost assets shipped by `maxiviper117/result-flow` so app teams can quickly see what guidance and skills are included.

## When to use this

Use this page when your Laravel app uses Boost and you want ResultFlow-aware AI guidance without writing custom rules from scratch.

Official Boost docs: https://laravel.com/docs/12.x/boost

## Package-provided Boost assets

This package includes the following Boost files:

- Guideline: `resources/boost/guidelines/core.blade.php`
- Skill: `resources/boost/skills/result-flow-laravel/SKILL.md`
- Skill: `resources/boost/skills/result-flow-debugging/SKILL.md`

### Included guideline

- `core.blade.php` defines conventions for:
  - Result pipeline structure (`ok`/`fail`, `then`, `ensure`, `otherwise`, `recover`)
  - Metadata discipline (`array<string,mixed>` propagation)
  - Laravel boundaries (`toResponse`, transaction rollback with `throwIfFail`)
  - Common anti-patterns to avoid

### Included skills

- `result-flow-laravel` helps scaffold explicit success/failure Laravel workflows using current `Result` APIs.
- `result-flow-debugging` helps add safe diagnostics and metadata-aware troubleshooting without changing branch semantics.

## Install and usage flow

In your Laravel app:

```bash
php artisan boost:install
```

Boost will discover package-provided guidelines and make them available in app AI context. Package-provided skills can be installed using Boost skill commands and are written into the app `.ai` directory.

## App-level overrides

You can add or override guidelines in your app:

- `.ai/guidelines/...`

To override a built-in guideline, match the same path pattern, for example:

- `.ai/guidelines/inertia-react/2/forms.blade.php`

## Choose package guideline vs app override

| Need | Use |
| --- | --- |
| Standard ResultFlow defaults from package maintainers | Package guideline in `resources/boost/guidelines/core.blade.php` |
| Team-specific conventions for one app | App-level `.ai/guidelines/...` |
| Reusable workflow generation for Laravel Result pipelines | `result-flow-laravel` skill |
| Failure-path diagnostics and metadata safety workflow | `result-flow-debugging` skill |

## Related pages

- [Getting Started](/getting-started)
- [Laravel Workflow Example](/examples/laravel)
- [API Reference](/api)
- [README (repository)](https://github.com/Maxiviper117/result-flow/blob/main/README.md)
