---
title: Laravel Boost
---

# Laravel Boost

## Introduction

Laravel Boost uses two package asset types:

- Guidelines define ResultFlow usage conventions for AI agents writing code in downstream Laravel apps.
- Skills define task-focused workflows for those downstream apps.

In short: guidelines shape app-level coding behavior; the central skill orchestrates concept workflows through local references.

## What this page is for

This page documents the Boost assets shipped by `maxiviper117/result-flow` and explains how consumer apps use them.

## When to use this

Use this page when your Laravel app uses Boost and you want ResultFlow-aware AI guidance without writing custom rules from scratch.

Official Boost docs: https://laravel.com/docs/12.x/boost

## Package-shipped Boost assets

This package ships the following source assets:

- Guideline source: `resources/boost/guidelines/core.blade.php`
- Central skill source: `resources/boost/skills/result-flow/SKILL.md`
- Skill references:
  - `resources/boost/skills/result-flow/references/constructing.md`
  - `resources/boost/skills/result-flow/references/chaining.md`
  - `resources/boost/skills/result-flow/references/failure-handling.md`
  - `resources/boost/skills/result-flow/references/batch-processing.md`
  - `resources/boost/skills/result-flow/references/boundaries.md`
  - `resources/boost/skills/result-flow/references/retries.md`
  - `resources/boost/skills/result-flow/references/debugging-metadata.md`
  - `resources/boost/skills/result-flow/references/public-api-whitelist.md`

These files are maintained in this package repository, but their purpose is to guide AI behavior in downstream consumer applications.

### Included guideline

- `core.blade.php` defines app-usage conventions for:
  - Result pipeline structure (`ok`/`fail`, `then`, `ensure`, `otherwise`, `recover`)
  - Metadata discipline (`array<string,mixed>` propagation)
  - Laravel boundaries (`toResponse`, transaction rollback with `throwIfFail`)
  - Common anti-patterns to avoid in app code

### Included central skill + references

- `result-flow/SKILL.md` provides orchestration rules:
  - detect user intent
  - load only needed local reference docs
  - keep progressive disclosure and avoid unnecessary context loading
- `result-flow/references/*` contains concept-depth guidance:
  - constructing, chaining, failure handling, batch processing
  - boundaries, retries, debugging/metadata
  - public API whitelist

## Install and usage flow

In your Laravel app:

```bash
php artisan boost:install
```

Boost discovers package-shipped assets and applies them in the app AI context. Skill assets are installed into the app `.ai` directory using Boost skill install commands.

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
| Central ResultFlow skill orchestration | `resources/boost/skills/result-flow/SKILL.md` |
| Detailed concept guidance | `resources/boost/skills/result-flow/references/*.md` |

## Related pages

- [Getting Started](/getting-started)
- [Laravel Workflow Example](/examples/laravel)
- [Composition Patterns](/result/compositions)
- [API Reference](/api)
- [README (repository)](https://github.com/Maxiviper117/result-flow/blob/main/README.md)
