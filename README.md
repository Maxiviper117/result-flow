# Result Flow

A synchronous, framework-independent Result type for PHP 8.4 and later. It makes expected failures explicit and carries diagnostic metadata through a workflow.

```php
use Maxiviper117\ResultFlow\Result;

$validate = fn (int $value): Result => $value > 0
    ? Result::ok($value)
    : Result::fail('Must be positive');

$message = Result::ok(' 42 ')
    ->map(fn (string $input): int => (int) trim($input))
    ->andThen($validate)
    ->match(
        onSuccess: fn (int $value): string => "Accepted: $value",
        onFailure: fn (string $error): string => "Rejected: $error",
    );
// Accepted: 42
```

After v2 is available on Packagist, install it with `composer require 'maxiviper117/result-flow:^2.0'`. To try v2 before release, follow the checkout instructions in the [order tutorial](docs/getting-started.md).

`map` transforms a value. `andThen` requires a Result-returning callback. `mapError` transforms a failure, and `orElse` provides Result-returning recovery. Callbacks receive one payload and thrown exceptions propagate. `Result::attempt($callback, catch: ExpectedException::class)` selectively captures an exception and wraps the callback's return without flattening it.

`andThen` and `orElse` shallow-merge metadata, with returned Result keys taking precedence. Use `withMeta()` to replace the bag. Diagnostic metadata should not be exposed to clients unless explicitly allowlisted. `unwrap()` throws failed results; non-Throwable failure values are retained by `UnwrapFailure`.

For collections, `ResultBatch` offers keyed first-failure and all-errors operations. `Retry` and `ResourceScope` provide retry and resource cleanup helpers. `Output\Serialization` includes metadata in its output; map public responses explicitly. `Output\DebugOutput` produces sanitized diagnostics.

The VitePress documentation is in [docs](docs/). Laravel Boost guidance ships in `resources/boost/guidelines/` and `resources/boost/skills/`.

Development tests include a real SQLite-backed Laravel transaction. The Laravel database component is a development dependency only; it is not required to use the core package.

The built-in debug sanitizer bounds nested arrays by default. XML output requires SimpleXML and rejects invalid UTF-8 or XML 1.0 characters. Retry delays block execution and throw `OverflowException` before the retry hook when the maximum possible delay including jitter exceeds 2,147,483,647 ms; filter permanent failures and use idempotency keys for retried writes.

Applications using PHPStan can opt into `phpstan-result-flow.neon` to detect discarded Result-returning calls; see the [guide](docs/guides/unused-results.md) for limitations and intentional suppression.

`ResultBatch::firstSuccess()` selects the first success from a non-empty collection, and `zip()` combines two evaluated Results. For field-level validation, `mapAllErrors()` can collect arrays of messages by field.

Structured error causes can be nested with `Cause`. Serialization throws `LengthException` beyond 32 levels or 256 visited cause nodes. `DebugOutput` truncates the cause and exposes it in the sanitized `error_cause` field.

`Retry::attempt()` preserves the inferred success and failure types of the operation. Its failure type also includes `Throwable` for captured exceptions.

`Output\ProblemDetails::response()` builds an explicit RFC 9457 problem JSON response from fields chosen by the application; it does not automatically expose Result errors or metadata and rejects bodyless HTTP statuses (1xx, 204, 205, and 304).

Follow the [order tutorial](docs/getting-started.md) to run both a successful save and a rejected input, or see the [Laravel controller example](docs/laravel-boost.md) for HTTP response mapping.

JSON serialization rejects `JSON_PARTIAL_OUTPUT_ON_ERROR` so encoding failures throw. XML serialization rejects recursive arrays and arrays at 128 nesting levels. Debug output bounds structured cause expansion before sanitization. See [boundary serialization](docs/reference/boundaries.md) and [metadata and debugging](docs/reference/metadata-debugging.md).
