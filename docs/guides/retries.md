# Retrying operations

## What this page is for

Use `Retry` to repeat an operation after a transient failure.

## When to use this

Retry temporary failures only. Validation failures and other permanent errors should fail immediately. Retried writes need an idempotency key or another way to prevent duplicate effects.

## Configure retries

```php
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Retry;

$calls = 0;
$fetch = function () use (&$calls): Result {
    return ++$calls === 1 ? Result::fail('busy') : Result::ok('available');
};

$result = Retry::config()
    ->maxAttempts(3)
    ->when(fn (mixed $error): bool => $error === 'busy')
    ->attachAttemptMeta()
    ->attempt($fetch);

echo $result->unwrap(); // available
echo $result->meta()['retry']['attempts']; // 2
```

By default all failures are eligible for retry, up to `maxAttempts` (one attempt by default). `attempt` accepts callbacks returning a raw value or a Result, preserves the success and failure types, and adds `Throwable` to the failure type for captured exceptions; exceptions from `when` and `onRetry` propagate. Delays block the current PHP process. `delay`, `exponential`, and `jitter` control the wait, and `attachAttemptMeta` records the number of attempts.

- [Failure handling](/concepts/failure-handling)

## Choose an attempt budget

| Setting | Consequence for a continually failing operation |
| --- | --- |
| `maxAttempts(1)` | The initial call consumes the budget |
| `maxAttempts(3)->delay(100)` | At most three calls with two 100 ms waits |
| Add `exponential()` to that configuration | The waits become 100 ms and 200 ms |

An early success or a false retry predicate ends the sequence sooner. When measuring request latency, allow for both the waits and the time spent inside each operation.

## Related pages

- [Construction and exception capture](/reference/construction)
