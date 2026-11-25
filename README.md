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

After a v2 beta is published, install it with `composer require 'maxiviper117/result-flow:^2.0@beta'`. The unqualified package constraint may still select stable v1 before a stable v2 release. `map` transforms a value; `andThen` requires a Result-returning callback. `mapError` transforms a failure, and `orElse` provides Result-returning recovery. Callbacks receive one payload and thrown exceptions propagate. `Result::attempt($callback, catch: ExpectedException::class)` selectively captures an exception and wraps the callback's return without flattening it.

`andThen` and `orElse` shallow-merge metadata, with returned Result keys taking precedence. Use `withMeta()` to replace the bag. Diagnostic metadata should not be exposed to clients unless explicitly allowlisted. `unwrap()` throws failed results; non-Throwable failure values are retained by `UnwrapFailure`.

For collections, `ResultBatch` offers keyed first-failure and all-errors operations. `Retry` and `ResourceScope` are separate compatibility helpers. `Output\LegacyOutput` and `Laravel\LegacyResponse` preserve v1 output shapes, including metadata; use explicit `match` mappings for new endpoints. `Output\DebugOutput` produces sanitized diagnostics.

The VitePress documentation is in [docs](docs/). Laravel Boost guidance ships in `resources/boost/guidelines/` and `resources/boost/skills/`.

Development tests include a real SQLite-backed Laravel transaction. The Laravel database component is a development dependency only; it is not required to use the core package.

The built-in debug sanitizer bounds nested arrays by default. Legacy XML output requires SimpleXML and rejects invalid UTF-8 or XML 1.0 characters. Retry delays block execution; filter permanent failures and use idempotency keys for retried writes.

Applications using PHPStan can opt into `phpstan-result-flow.neon` to detect discarded Result-returning calls; see the [guide](docs/guides/unused-results.md) for limitations and intentional suppression.
