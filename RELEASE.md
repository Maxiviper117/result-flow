# Release Process

This project uses [Google's Release Please](https://github.com/googleapis/release-please) to automate the release process. This automation handles version bumping, changelog generation, and creating GitHub releases based on **Conventional Commits**.

## 1. Commit Messages

All commits to the `main` branch **must** follow the [Conventional Commits](https://www.conventionalcommits.org/) specification. This is crucial because `release-please` analyzes these messages to determine the next version number and generate the changelog.

**Common Types:**

*   `feat: ...` -> Triggers a **MINOR** version bump (e.g., `1.1.0` -> `1.2.0`).
*   `fix: ...` -> Triggers a **PATCH** version bump (e.g., `1.1.0` -> `1.1.1`).
*   `perf: ...` -> Triggers a **PATCH** version bump.
*   `chore: ...` -> No release trigger (usually).
*   `docs: ...` -> No release trigger (usually).
*   `test: ...` -> No release trigger (usually).
*   `refactor: ...` -> No release trigger (usually).
*   `style: ...` -> No release trigger (usually).
*   `ci: ...` -> No release trigger (usually).

**Breaking Changes:**

To trigger a **MAJOR** version bump (e.g., `1.0.0` -> `2.0.0`), append `!` to the type or include `BREAKING CHANGE:` in the footer.

*   `feat!: remove deprecated API`
*   `fix!: change return type of public method`

## 2. Merging Pull Requests

To ensure `release-please` can accurately parse changes, this repository is configured to **only allow Squash Merges**. 

When merging a Pull Request, ensure the squashed commit message (which defaults to the PR title) follows the Conventional Commits specification. This single squashed commit is what `release-please` uses to determine the next release.

## 3. The Release Workflow

The release process is fully automated via GitHub Actions:

1.  **Work as usual**: Create branches, open PRs, and merge them into `main`. Ensure commit messages are "conventional".
2.  **Release PR**: When changes are merged to `main`, the `release-please` action runs. If it detects releasable changes (feat, fix, etc.), it will **automatically open (or update) a Pull Request** titled something like `chore(main): release 1.x.x`.
    *   This PR contains the updated `CHANGELOG.md` and the version bump in `.release-please-manifest.json` (and `composer.json` if configured).
3.  **Review**: Review the Release PR. You can wait for more features to be merged; the PR will update automatically.
4.  **Merge**: When you are ready to release, **merge the Release PR**.
5.  **Release**: Once merged, the action will automatically:
    *   Create a new GitHub Release.
    *   Create a git tag (e.g., `v1.2.0`).
    *   Publish the release notes.

## 4. Manual Triggers (Force Release)

If `release-please` is not picking up a change or you need to force a release, you can:

1.  Run the workflow manually from the "Actions" tab if configured with `workflow_dispatch`.
2.  Or, create an empty commit with a conventional message to trigger the desired bump:
    ```bash
    git commit --allow-empty -m "chore: release 1.0.1" -m "Release-As: 1.0.1"
    git push
    ```

## 5. Packagist

Packagist is configured to automatically update when a new tag is pushed to the repository. No manual action is required after the Release PR is merged.