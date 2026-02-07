---
name: vitepress
description: Hard-policy workflow for rewriting, reviewing, and operating this repository's VitePress documentation with mandatory quality gates.
---

# VitePress Documentation Skill (Hard Policy)

## Purpose

Use this skill when editing VitePress docs in this repository.

This skill is not optional guidance. It defines required workflow, required page templates, and completion gates.

## Trigger conditions

Use this skill when the user asks to:
- rewrite docs
- add/update API docs
- improve docs structure or navigation
- review docs quality/consistency
- update VitePress config or docs build behavior

## Source-of-truth files

Always inspect these first:
- `.vitepress/config.mts`
- `docs/`
- `README.md`
- `src/Result.php`
- `src/Support/` (for behavior details not obvious at API surface)
- `package.json` (docs scripts)

## Mandatory workflow

Follow all steps in order.

1. Discovery pass
- Inventory all docs pages and current nav/sidebar.
- Inventory all public API methods from `src/Result.php`.
- Identify broken/inconsistent naming between docs and API.

2. IA impact check
- Determine whether request is content-only, nav-only, or full IA rewrite.
- If IA changes, produce explicit old->new page mapping in notes.

3. Contract extraction
- For every public method, extract:
  - callback contract
  - success behavior
  - failure behavior
  - exception behavior
  - metadata behavior

4. Draft/rewrite with required templates
- Use the required templates in this skill (below).
- Keep examples minimal, executable, and type-safe.
- Use plain PHP examples first; framework examples second.

5. Cross-linking pass
- Add related links between guide pages and API sections.
- Ensure each major guide links to API and at least one example.

6. Consistency pass
- Normalize method naming, headings, and terminology.
- Verify decision tables use same method names as API.

7. Validation gates
- Run docs build.
- Resolve broken links and missing pages.
- Verify coverage map for public methods.

## Required templates

### Guide page template
Each guide page must include:
1. `What this page is for`
2. `When to use this`
3. Core concepts with at least one code example
4. `Choose X vs Y` table if methods are easily confused
5. `Related pages`

### API method template
Each method section must include:
1. Method name/signature line
2. Contract
3. Behavior details (success/failure/exception/metadata as applicable)
4. At least one concise example
5. Link to one guide page

### Example page template
Each example page must include:
1. Context/problem statement
2. Complete snippet
3. Expected result shape or branch outcome
4. Link to relevant API and guide pages

## Hard completion gates

Do not mark docs work complete unless all are true:
- No broken local links in VitePress build output.
- Every public method from `src/Result.php` is documented in API reference.
- Core guide pages include required decision tables.
- Plain PHP examples exist for major method groups.
- `.vitepress/config.mts` nav/sidebar reflects actual page set.
- `README.md` docs links align with current IA.

## Reporting format

When finished, report:
1. Changed files list
2. API coverage map: `method -> doc section`
3. Validation results (`docs:build`, plus any relevant checks)
4. Remaining risks or intentional tradeoffs

## Command policy

Allowed without extra approval:
- file reads/searches
- `pnpm run docs:build`

Ask before running:
- `pnpm install`
- `pnpm run docs:dev`

## Style policy

- Prefer short paragraphs and explicit bullet behavior rules.
- Avoid ambiguous terms like "handles it" without branch details.
- Keep examples deterministic and environment-light.
- Preserve ASCII unless file already requires Unicode.
