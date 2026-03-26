---
name: ResultFlow App Usage
description: Central orchestration skill for using ResultFlow in downstream Laravel apps; load only the relevant local reference docs for constructing, chaining, failure handling, batch, boundaries, retries, debugging, and API whitelist.
---

# ResultFlow App Usage

## Mission and scope

Generate and refine ResultFlow-based code in downstream Laravel applications using only the package's public APIs.

## Activation criteria

Use this skill when the user asks for ResultFlow workflow design, implementation, debugging, retries, batch processing, or boundary/output decisions.

## High-level workflow

1. Identify the primary intent (construct, chain, failure map, batch, boundary, retry, debug).
2. Use the kitchen-sink pages when you need a grouped tour of related methods before picking one.
3. Load only the matching `references/*.md` file(s) for exact signatures or edge behavior.
4. Keep branch semantics explicit (`ok`/`fail`) and preserve metadata.
5. End flows intentionally at app boundaries (`match`, `toResponse`, `unwrap*`, `throwIfFail`).
6. Prefer `defer(...)` over `of(...)` when the callback may already return a `Result`.

## Progressive disclosure rule

- Do not load every reference file by default.
- Start with the minimum needed reference file.
- Add additional references only if the user request spans multiple concepts.

## Hard constraints

- Use only documented public `Result` methods.
- Do not invent APIs or depend on package internal helper classes.
- Keep behavior deterministic and consumer-facing error shapes stable.
- Follow host-application coding standards, tests, and CI constraints.

## Local references

- `references/constructing.md`
- `references/chaining.md`
- `references/failure-handling.md`
- `references/batch-processing.md`
- `references/boundaries.md`
- `references/retries.md`
- `references/debugging-metadata.md`
- `references/public-api-whitelist.md`
