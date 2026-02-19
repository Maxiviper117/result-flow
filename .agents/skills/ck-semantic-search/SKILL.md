---
name: ck-semantic-search
description: Use ck (BeaconBay/ck) to index and semantically search a codebase, returning the most relevant files and snippets with clear next-step suggestions. Trigger when users mention "ck", "semantic grep", "semantic search", "find relevant code", "where is the logic for", "search the repo for meaning", or "use ck to search".
---

# ck-semantic-search

Use the `ck` CLI to perform fast semantic and hybrid searches over a repository.

## Primary goals

- Find the most relevant places in the codebase for the user's intent.
- Return actionable results: file paths, short snippets, and what to inspect next.
- Use safe defaults: avoid indexing huge irrelevant directories and avoid noisy output.

## How ck works (high level)

- Parse files into semantic chunks (tree-sitter where available), embed chunks, and store vectors in a local ANN index under `.ck/`.
- Auto-build and incrementally update the index for `--sem`, `--lex`, and `--hybrid`; subsequent searches reprocess only changed files.
- Run offline; no code or queries are sent externally.

## Preconditions

- Confirm `ck` is available with `ck --version`.
- If `ck` is missing, provide install guidance and ask the user before installing:
  - Rust install: `cargo install ck`
  - Or download a release binary

## Repository indexing rules

### When to index

- If no `.ck/` index exists in the repo root, index immediately.
- If the user changed many files or complains results are stale, re-index.
- If `ck` reports index issues, re-index.
- Remember: `--sem`, `--lex`, and `--hybrid` will index automatically on first run.
- The `.ck/` index is safe to delete if you need to reclaim space.

### Default ignore behavior

`ck` respects `.gitignore` and `.ckignore` by default. A `.ckignore` is created automatically on first index, uses `.gitignore` syntax (globs, `!` for negation), and excludes non-code noise (images, binaries, archives, JSON/YAML config) by default. If results are missing, check ignore rules or add targeted exclusions.

Common project exclusions to consider adding:

- `node_modules/`
- `vendor/`
- `dist/`
- `build/`
- `.next/`
- `.turbo/`
- `.cache/`
- `storage/` (Laravel, optional depending on needs)
- `public/build/` (Laravel/Vite output)

Troubleshooting commands when results differ from grep:

```
ck --no-ignore --no-ckignore "<pattern>" .
```

### Index commands

- Check status: `ck --status .`
- Standard index: `ck --index .`
- Full rebuild (only if needed): `ck --clean .` then `ck --index .`

## Search modes and when to use them

### 1) Semantic / hybrid (default)

Use for "find logic" or "where is code that does X".

Command:

```
ck --sem "<natural language query>" [path] --limit <N> -C <C>
```

Tips:

- Use `--scores` when you need to judge relevance quickly.
- Lower `--threshold` (e.g., 0.4) if results are too sparse; raise it for precision.
- Use semantic search for concepts, not exact strings; use grep/regex for exact symbols.

### 2) Lexical (BM25) mode

Use when you have meaningful terms or phrases and want ranked full-text matches.
If you need exact symbols or identifiers, prefer plain grep or `--regex`.

Command:

```
ck --lex "<token or phrase>" [path] --limit <N> -C <C>
```

### 3) Regex / grep-compatible mode (default)

Use for patterns and narrow structural searches.

Command:

```
ck --regex "<pattern>" [path] --limit <N> -C <C>
```

Examples (grep-compatible):

```
ck "TODO|FIXME" src/
ck -i "warning" src/
ck -n "pattern" src/
```

### 4) Hybrid mode

Use when you have a keyword and want semantic recall plus lexical precision. Hybrid search fuses lexical and semantic rankings with Reciprocal Rank Fusion (RRF).

Command:

```
ck --hybrid "<query>" [path] --limit <N> -C <C>
```

## Query formulation tips

- Describe what the code does, not just the name (e.g., "error handling", "authentication logic").
- Avoid vague queries like "code" or "main thing".
- Scope to subpaths (`src/`, `tests/`, `config/`) to reduce noise.
- Use grep-style search for exact strings: `ck "Result::ok" src/`.

## Repo query recipes

Use these patterns as starting points, then refine:

```bash
# Core Result API and constructors
ck --sem "Result success failure container" src --limit 5 -C 2
ck --regex "final class Result" src -n -C 2
ck "Result::ok" src -n -C 1
ck "Result::fail" src -n -C 1

# Metadata flow and propagation
ck --sem "metadata propagation merge meta" src --limit 5 -C 2 --threshold 0.4
ck --regex "meta\\(" src -n -C 1

# Chaining behavior
ck --sem "then ensure otherwise recover" src --limit 8 -C 2 --threshold 0.4
ck --regex "->(then|ensure|otherwise|recover)" src -n -C 1

# Matching and unwrap behavior
ck --sem "match on success failure" src --limit 5 -C 2
ck --sem "unwrap throw exception" src --limit 5 -C 2

# Operations (batch, retry, pipeline)
ck --sem "batch mapping" src/Support/Operations --limit 5 -C 2
ck --sem "retry attempts backoff" src/Support/Operations --limit 5 -C 2
ck --sem "pipeline step chain" src/Support/Operations --limit 5 -C 2

# Laravel integration
ck --sem "toResponse HTTP response integration" src/Laravel --limit 5 -C 2
ck --regex "ResultResponse" src/Laravel -n -C 2

# Debug and serialization output
ck --sem "debug output redaction serialization" src/Support/Output --limit 5 -C 2
ck --regex "to(Debug)?Array" src -n -C 1
```

## Output expectations

- Summarize the top matches with file paths and short snippets.
- Call out the most likely entry point and 1-3 next files to inspect.
- If results are noisy, tighten scope with a subpath or switch to lexical/regex mode.
- If snippets are too shallow, increase context with `-C` or use `--full-section` (supported languages only).
