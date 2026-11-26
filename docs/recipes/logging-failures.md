# Log a failed order action

An application can choose the severity of a failure according to its own operations. This example sends an expected missing-order failure to `notice`, a failed write to `error`, and an unmapped failure to `warning`. It accepts the application's existing [PSR-3 logger](https://www.php-fig.org/psr/psr-3/).

The snippet is complete once `$logger` is supplied by the application. The package's diagnostic serializer applies its configured sanitizer before the example selects the trace ID.

```php
use Maxiviper117\ResultFlow\Output\DebugOutput;
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\DataTaggedError;
use Maxiviper117\ResultFlow\Support\Errors\ResultError;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/** @param Result<mixed, mixed> $result */
function logOrderFailure(Result $result, LoggerInterface $logger): void
{
    $result->match(
        onSuccess: static function (mixed $value): void {},
        onFailure: static function (mixed $error) use ($result, $logger): void {
            $code = $error instanceof ResultError ? $error->code() : null;
            $level = match ($code) {
                'order.not_found' => LogLevel::NOTICE,
                'order.write_failed' => LogLevel::ERROR,
                default => LogLevel::WARNING,
            };

            $debug = DebugOutput::toArray($result);
            $meta = is_array($debug['meta']) ? $debug['meta'] : [];
            $traceId = $meta['trace_id'] ?? null;

            $logger->log($level, 'Order action failed', [
                'error_code' => $debug['error_code'],
                'error_type' => $debug['error_type'],
                'trace_id' => is_string($traceId) ? $traceId : null,
            ]);
        },
    );
}

$missing = Result::fail(new DataTaggedError('order.not_found', 'Order not found'));
$writeFailed = Result::fail(
    new DataTaggedError('order.write_failed', 'Write failed'),
    ['trace_id' => 'req-42', 'api_token' => 'example-secret'],
);

logOrderFailure($missing, $logger);                 // notice
logOrderFailure($writeFailed, $logger);             // error, trace_id req-42
logOrderFailure(Result::fail('unknown'), $logger); // warning
logOrderFailure(Result::ok(['id' => 42]), $logger); // no log entry
```

The failure branches emit the same fixed message with different levels. With the default sanitizer, `DebugOutput::toArray($writeFailed)['meta']['api_token']` is `***REDACTED***`. The logger receives only the three selected fields, so the token is absent from the log context. A custom or disabled sanitizer changes that diagnostic output, and applications should review their own log policy and selected fields.

See [metadata and debugging](/reference/metadata-debugging) for sanitizer configuration and [failure handling](/reference/failure-handling) for structured error contracts.
