---
name: blume
description: Inspect and operate the repository's Blume documentation site: review configuration, list pages, verify base/search/CI settings, and (with permission) run dev/build commands.
---

# Blume skill

This skill helps an agent inspect, validate, and operate the Blume-based documentation site in this repository.

## Workflow

1. Read the following files and directories (relative to repo root): package.json, blume.config.ts, docs/, .github/workflows/deploy-docs.yml, and .gitignore.
2. Extract and report:
   - package.json: scripts.docs:dev/docs:build/docs:preview/docs:validate, devDependencies.blume
   - blume.config.ts: content.root, deployment.base, title, search.provider, navigation.tabs/featured
   - docs/: page inventory and folder meta.ts files
   - CI workflow: that build step runs `pnpm run docs:build` and uploads `dist`
   - .gitignore: presence of `.blume/`
3. When asked for a structured report:
   - Return a JSON object with keys: content_root, base, title, search_provider, docs_count, scripts, blume_version, ci_deploy_path, issues (array)
4. When asked to validate build:
   - Run `pnpm install --frozen-lockfile` (if needed), then `pnpm run docs:build` and return build status, warnings, and output path `dist/`.
   - Ask before running `pnpm run docs:dev` (dev server).

## Examples

- Input: "Review Blume config and list potential issues"
- Output: JSON report with config summary and issues list

## Notes

- Blume supports both `.md` and `.mdx` content files.
- Report the installed blume version from package.json.
- Navigation can be filesystem-driven (`meta.ts`) or explicit in `blume.config.ts`.

## Key files

- blume.config.ts
- docs/
- package.json
- .github/workflows/deploy-docs.yml
