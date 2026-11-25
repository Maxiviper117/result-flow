# Result Flow

A synchronous, framework-independent Result type for PHP 8.4 and later. It makes expected failures explicit and carries diagnostic metadata through a workflow.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok($input, ['trace_id' => $traceId])
    ->map($normalizer->normalize(...))
    ->andThen($validator->validate(...))
    ->andThen($orders->save(...));

return $result->match(
    onSuccess: fn ($order) => response()->json(['id' => $order->id], 201),
    onFailure: fn ($error) => $responses->forOrderError($error),
);
```

Install with `composer require maxiviper117/result-flow`. `map` transforms a value; `andThen` requires a Result-returning callback. `mapError` transforms a failure, and `orElse` provides Result-returning recovery. Callbacks receive one payload and thrown exceptions propagate. `Result::attempt($callback, catch: ExpectedException::class)` selectively captures an exception and wraps the callback's return without flattening it.

`andThen` and `orElse` shallow-merge metadata, with returned Result keys taking precedence. Use `withMeta()` to replace the bag. Diagnostic metadata should not be exposed to clients unless explicitly allowlisted. `unwrap()` throws failed results; non-Throwable failure values are retained by `UnwrapFailure`.

For collections, `ResultBatch` offers keyed first-failure and all-errors operations. `Retry` and `ResourceScope` are separate compatibility helpers. `Output\LegacyOutput` and `Laravel\LegacyResponse` preserve v1 output shapes, including metadata; use explicit `match` mappings for new endpoints. `Output\DebugOutput` produces sanitized diagnostics.

The VitePress documentation is in [docs](docs/). Laravel Boost guidance ships in `resources/boost/guidelines/` and `resources/boost/skills/`.

Development tests include a real SQLite-backed Laravel transaction. The Laravel database component is a development dependency only; it is not required to use the core package.
