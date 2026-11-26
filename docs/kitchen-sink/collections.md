# Collections

`ResultBatch::firstFailure` and `allErrors` aggregate evaluated Results; `mapFirstFailure` and `mapAllErrors` invoke callbacks on items. They preserve keys. The all-errors methods return errors only on failure. See the [batch reference](/reference/batch-processing).

| I want to… | Call |
| --- | --- |
| Collect values when every check passes | `firstFailure($results)` |
| Show all field errors together | `allErrors($results)` |
| Stop invoking an expensive check after a failure | `mapFirstFailure($items, $check)` |
| Validate every field and retain its key | `mapAllErrors($items, $check)` |
| Choose the first available result | `firstSuccess($results)` |
| Combine two successes into an ordered pair | `zip($left, $right)` |

See [batch strategy](/guides/batch-strategy) for a worked validation example.

For a form, use the field name as the input key so the UI can place each error beside its field. For two successful lookups, `zip` gives you a positional pair: the left value is at index 0 and the right value at index 1. Use [the batch examples](/reference/batch-processing#predict-the-result) to compare the shapes.
