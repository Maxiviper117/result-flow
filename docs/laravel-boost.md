---
title: Laravel Boost
---

# Laravel Boost

Result Flow ships Boost assets for downstream Laravel applications that use AI-assisted code generation.

## What is shipped

- `resources/boost/guidelines/core.blade.php`
- `resources/boost/skills/result-flow/SKILL.md`
- `resources/boost/skills/result-flow/references/*.md`

## What those assets do

- the guideline describes app-level conventions for using `Result`
- the skill orchestrates ResultFlow-related tasks
- the reference files give task-specific guidance for construction, chaining, failure handling, retries, batches, boundaries, and observability

## What app teams should remember

- use public APIs only
- keep metadata intact
- normalize failures at boundaries
- prefer `toDebugArray()` for logs
- use `thenUnsafe()` only when exception bubbling is intended

Official Boost docs: [Laravel Boost](https://laravel.com/docs/12.x/boost)

## Related pages

- [Getting started](/getting-started)
- [Reference overview](/reference/)
