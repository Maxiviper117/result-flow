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

3. Update `CHANGELOG.md`:

   * Move items from **Unreleased** into a new version section.
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

1. Go to **Releases** → **New Release**.
2. Select the pushed tag.
3. Title it: `vX.Y.Z`.
4. Paste the corresponding `CHANGELOG.md` section into the description.
5. Add upgrade notes if the release includes breaking changes.
6. Publish.
7. (Optional but recommended) Add the release link to the bottom of `CHANGELOG.md` so the changelog contains a permalink for the release. Example:

```markdown
[vX.Y.Z]: https://github.com/Maxiviper117/result-flow/releases/tag/vX.Y.Z
```

   You can automate this step with a changelog updater GitHub Action (see `.github/workflows/update-changelog.yml`).

**Note (automated flow):** You should not need to manually edit `CHANGELOG.md` after publishing a release. Instead:

- Prepare the `Unreleased` section while developing (or put intended notes in the Release body when creating the GitHub Release).
- Publish the GitHub Release with the version as the Release title (e.g. `v0.1.0`) and release notes in the Release body.

The workflow at `.github/workflows/update-changelog.yml` will run on the `released` event, insert the release notes into `CHANGELOG.md`, and commit the updated changelog back to `main` automatically. Manual edits are only necessary to correct formatting or to add custom links that the updater didn't create.

Alternatively, GitHub Actions will auto-generate a release when a tag matching `v*` is pushed.

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

