# Changelog

All notable changes to `maxiviper117/result-flow` will be documented in this file.

## Unreleased

### Changed

- Adjusted `toDebugArray()` typing to reflect custom sanitizer output.
- `failed_step` metadata now records the most recent failure in a chain.

### Fixed

- `toDebugArray()` no longer depends on mbstring being installed.
- `ensure()` treats string errors as values, even if they match callable names.
- `throwIfFail()` now produces deterministic error messages for non-encodable values.

### Documentation

- Updated failure metadata and `ensure()` behavior notes in the guides.

## v0.1.2 - 2025-12-01

### What's Changed

* Enhance Laravel debug sanitization and configuration by @Maxiviper117 in https://github.com/Maxiviper117/result-flow/pull/5

## v0.1.1 - 2025-11-30

- Support callable array steps in runChain method (PR #4)

## v0.1.0 - 2025-11-30

Release v0.1.0
