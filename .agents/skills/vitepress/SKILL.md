---
name: vitepress
description: Inspect and operate the repository's VitePress documentation site review configuration, list pages, verify base/search/CI settings, and (with permission) run dev/build commands.
---

# VitePress skill

Overview
This skill helps an agent inspect, validate, and operate the VitePress-based documentation site in this repository.

When to use

- When asked to review docs configuration, verify search/indexing, confirm CI/deploy settings, or build/preview the site (ask permission before running commands).

How to operate

1. Read the following files and directories (relative to repo root): package.json, .vitepress/config.mts, docs/, .github/workflows/deploy-docs.yml, and .gitignore.
2. Extract and report these values:
    - package.json: scripts.docs:dev/docs:build/docs:preview, devDependencies.vitepress
    - .vitepress/config.mts: srcDir, base, title, themeConfig.search.provider, themeConfig.sidebar/nav
    - docs/: list top-level pages and subfolders count
    - CI workflow: that build step runs `pnpm run docs:build` and uploads `.vitepress/dist`
    - .gitignore: presence of `.vitepress/cache/`
3. Validate common constraints:
    - base must start and end with `/` for GitHub Pages deployments (e.g., `/owner/repo/`)
    - srcDir must exist and contain index pages
    - local search requires no external keys; algolia requires appId/apiKey/indexName
4. Reporting format:
    - Return a JSON object with keys: srcDir, base, title, search_provider, docs_count, scripts, vitepress_version, ci_deploy_path, issues (array)
5. If user permits running commands:
    - Run `pnpm install --frozen-lockfile` (if needed), then `pnpm run docs:build` and return build status, warnings, and output path `.vitepress/dist`.
    - Ask before running `pnpm run docs:dev` (dev server).

Examples

- Input: "Review VitePress config and list potential issues"
  Output: JSON with populated fields and a short human-readable summary.

Edge cases

- VitePress v2 alpha behavior and plugins may differ from v1; report the installed vitepress version.
- If docs are generated in a different outDir, report it.
- If base is `/` but site deployed to GitHub Pages at `owner/repo`, suggest setting base to `/result-flow/`.

Files referenced

- .vitepress/config.mts
- package.json
- docs/
- .github/workflows/deploy-docs.yml
- .gitignore

Security and permissions

- Do not run package installs or build commands without explicit user approval. When running commands, avoid exposing secrets and report any prompts or failures.
