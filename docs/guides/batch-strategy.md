# Batch strategy

## What this page is for

Choose whether to stop at the first failure or collect all validation errors, and whether work is already evaluated.

## When to use this

Use `ResultBatch` for keyed collections when every callback returns a Result.

```php
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\ResultBatch;

$result = ResultBatch::mapAllErrors(['a' => '', 'b' => 'Ada'],
    fn (string $name): Result => $name === '' ? Result::fail('Required') : Result::ok($name));
// Fail(['a' => 'Required']); successes are not included in a failure.
```

## Choose an operation

| Inputs | First failure | All errors |
| --- | --- | --- |
| Already-evaluated Results | `firstFailure` | `allErrors` |
| Items and callback | `mapFirstFailure` | `mapAllErrors` |

Keys are preserved; callback exceptions propagate. Metadata is shallow-merged in iteration order through the results visited. `mapFirstFailure` never invokes later callbacks.

## Related pages

[Batch reference](/reference/batch-processing) and [collect errors recipe](/recipes/collecting-batch-errors).
