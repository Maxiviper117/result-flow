# State and metadata

| Method | Purpose |
| --- | --- |
| `isOk` / `isFail` | Inspect the explicit branch tag |
| `meta` | Read the diagnostic bag |
| `withMeta` | Replace the complete bag on a new Result |

See [boundary reference](/reference/boundaries) and [metadata reference](/reference/metadata-debugging).

Given `$found = Result::ok(null, ['trace' => 'r7'])`, `$found->isOk()` is true and `$found->meta()['trace']` is `'r7'`. Calling `$found->withMeta([])` creates a successful null with an empty bag. Assign that returned Result if subsequent steps should use the cleared context.
