# Failure handling

`mapError` changes the failure payload without recovering. `orElse` invokes a failure callback returning a replacement Result. Both skip Ok and propagate thrown exceptions. Use `attempt` at a boundary to capture a selected Throwable class; expected business failures should be returned directly. See the [failure reference](/reference/failure-handling).

To retain a cause when normalizing an error, do so explicitly. For a structured error, build a new `DataTaggedError` with a `Cause` describing the original error and its nested causes. `DebugOutput::toArray` includes the sanitized chain under `error_cause`; a recovery callback otherwise replaces the original error.

## Translate an error or try another action

```php
use Maxiviper117\ResultFlow\Result;

$lookup = Result::fail('missing');
$translated = $lookup->mapError(fn (string $code): string => "customer.$code");
$recovered = $lookup->orElse(fn (string $code): Result =>
    $code === 'missing' ? Result::ok('Guest') : Result::fail($code)
);
```

The translated lookup is still a failure, with the code `customer.missing`. The recovered lookup succeeds with `Guest`, so subsequent success callbacks run. Only recover when the fallback is a valid answer for the caller. See [failure handling contracts](/reference/failure-handling).
