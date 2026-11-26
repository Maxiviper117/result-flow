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

By default all failures are eligible for retry, up to `maxAttempts` (one attempt by default). `attempt` captures exceptions from the operation; exceptions from `when` and `onRetry` propagate. Delays block the current PHP process. `delay`, `exponential`, and `jitter` control the wait, and `attachAttemptMeta` records the number of attempts.

## Related pages

- [Construction and exception capture](/reference/construction)
- [Failure handling](/concepts/failure-handling)
