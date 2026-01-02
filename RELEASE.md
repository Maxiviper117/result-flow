Here is a clean, ready-to-drop `RELEASE.md` file you can put in any PHP package repo. It’s short, strict, and follows everything a Composer package maintainer actually needs.

---

# RELEASE.md

This document describes the workflow for creating a new release of this package.

---

## 1. Prepare the release

Before creating a tag:

1. Ensure the latest PRs are merged into `main`.

2. Confirm tests, static analysis, and coding standards pass:

   ```bash
   composer test
   composer phpstan
   composer format
   ```

3. Prepare release notes:

   * Determine the next version number based on the latest tag (follow semantic versioning: `MAJOR.MINOR.PATCH`).
     - Check the current latest version: `git describe --tags --abbrev=0` (or `gh release list --limit 1`).
   * Ensure `CHANGELOG.md` has an **Unreleased** section with the changes for this release (to be used by the automated workflow), or prepare the notes to paste into the GitHub release description.
   * Use bullet points for each change, grouped under categories like `### Added`, `### Fixed`, `### Changed`, etc., following the "Keep a Changelog" format.
   * Example:

     ```markdown
     ## [Unreleased](https://github.com/Maxiviper117/result-flow/compare/v1.0.0...HEAD)

     ### Added
     - New feature description

     ### Fixed
     - Bug fix description

     ### Changed
     - Change description
     ```

   * Follow semantic versioning (`MAJOR.MINOR.PATCH`).

4. Verify `composer.json` constraints (PHP version, dependencies, branch-alias if used).

---

## 2. Create the version tag

Tags **must** be created on the `main` branch **AFTER** merging:

```bash
git checkout main
git pull
git tag -a vX.Y.Z -m "Release vX.Y.Z"
git push origin vX.Y.Z
```

Use annotated tags only. Never modify or re-push an existing tag.

---

## 3. Publish the GitHub Release

Create and publish the release using GitHub CLI (ensure `gh` is authenticated and the tag is pushed):

```bash
gh release create vX.Y.Z --title "vX.Y.Z" --notes "Paste release notes here (copy from the Unreleased section in CHANGELOG.md or use prepared notes). Include upgrade notes if the release includes breaking changes."
```

The workflow at `.github/workflows/update-changelog.yml` will automatically update `CHANGELOG.md` with the release notes, add the version heading, and commit the changes back to `main`. Manual edits to `CHANGELOG.md` are only necessary for formatting corrections or adding custom links.

---

## 4. Packagist

If the package is on Packagist:

* Versions update automatically when new Git tags are pushed.
* Use the Packagist “Update” button only if the version doesn't appear.

---

## 5. Breaking changes

For major versions:

* Document changes in `UPGRADE-X.Y.md`.
* Deprecate features in the previous minor before removing them.
* Avoid mixing unrelated breaking changes in the same release.

---

