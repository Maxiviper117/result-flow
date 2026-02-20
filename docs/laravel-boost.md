---
title: Laravel Boost
---

# Laravel Boost

## Introduction

Laravel Boost uses two package asset types:

- Guidelines define ResultFlow usage conventions for AI agents writing code in downstream Laravel apps.
- Skills define task-focused workflows (for example Laravel pipelines or debugging flows) for those downstream apps.

In short: guidelines shape app-level coding behavior; skills shape app-level task execution.

## What this page is for

This page documents the Boost assets shipped by `maxiviper117/result-flow` and explains how consumer apps use them.

## When to use this

Use this page when your Laravel app uses Boost and you want ResultFlow-aware AI guidance without writing custom rules from scratch.

Official Boost docs: https://laravel.com/docs/12.x/boost

## Package-shipped Boost assets

This package ships the following source assets:

- Guideline source: `resources/boost/guidelines/core.blade.php`
- Skill source: `resources/boost/skills/result-flow-laravel/SKILL.md`
- Skill source: `resources/boost/skills/result-flow-debugging/SKILL.md`

These files are maintained in this package repository, but their purpose is to guide AI behavior in downstream consumer applications.

### Included guideline

- `core.blade.php` defines app-usage conventions for:
  - Result pipeline structure (`ok`/`fail`, `then`, `ensure`, `otherwise`, `recover`)
  - Metadata discipline (`array<string,mixed>` propagation)
  - Laravel boundaries (`toResponse`, transaction rollback with `throwIfFail`)
  - Common anti-patterns to avoid in app code

### Included skills

- `result-flow-laravel` helps scaffold explicit success/failure Laravel workflows using public `Result` APIs.
- `result-flow-debugging` helps add safe diagnostics and metadata-aware troubleshooting without changing branch semantics.

## Install and usage flow

In your Laravel app:

```bash
php artisan boost:install
```

Boost discovers package-shipped assets and applies them in the app AI context. Skills are installed into the app `.ai` directory using Boost skill install commands.

## App-level overrides

Consumer apps can add or override guidelines locally:

- `.ai/guidelines/...`

To override a built-in guideline, match the same relative path in `.ai/guidelines`, for example:

- `.ai/guidelines/inertia-react/2/forms.blade.php`

## Choose package defaults vs app overrides

| Need | Use |
| --- | --- |
| Default ResultFlow behavior in app AI guidance | Package-shipped guideline `resources/boost/guidelines/core.blade.php` |
| Team-specific conventions for one app | App-level `.ai/guidelines/...` |
| Reusable workflow generation for Laravel Result pipelines | `result-flow-laravel` skill |
| Failure-path diagnostics and metadata safety workflow | `result-flow-debugging` skill |

## Related pages

- [Getting Started](/getting-started)
- [Laravel Workflow Example](/examples/laravel)
- [Composition Patterns](/result/compositions)
- [API Reference](/api)
- [README (repository)](https://github.com/Maxiviper117/result-flow/blob/main/README.md)
