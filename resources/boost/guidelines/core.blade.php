{{-- Result Flow guidance for downstream applications. --}}

# Result Flow v2

Use `Maxiviper117\ResultFlow\Result` for expected outcomes. Construct with `ok($value, $meta)` or `fail($error, $meta)`; null is valid on either branch. Use `map` for a value transformation and `andThen` for a callable returning another Result. Use `mapError` to transform a failure and `orElse` for Result-returning recovery. All callbacks receive one active payload and ordinary callbacks do not catch exceptions.

```php
$result = Result::ok($input, ['trace_id' => $traceId])
    ->map($normalizer->normalize(...))
    ->andThen($validator->validate(...))
    ->andThen($orders->save(...));

return $result->match(
    onSuccess: fn ($order) => response()->json(['id' => $order->id], 201),
    onFailure: fn ($error) => $responses->forOrderError($error),
);
```

`andThen` and `orElse` shallow-merge metadata, with returned Result keys taking precedence, including null. `withMeta` replaces the bag. Do not expose diagnostic metadata to clients unless explicitly allowlisted.

At an exception boundary, use `Result::attempt($fn, catch: ExpectedException::class)` to capture only that exception type and its subclasses. It wraps the returned value, even another Result. `unwrap()` rethrows a Throwable failure or throws `UnwrapFailure` containing the original failure in `failure`. Returning a failure from a Laravel transaction does not cause rollback: throw inside the transaction when rollback is required.

`ResultBatch` provides keyed batch operations. `Retry::config()` and `ResourceScope::run()` are compatibility helpers that capture exceptions; configure retry predicates for transient failures. `Output\LegacyOutput` and `Laravel\LegacyResponse` preserve v1 output, including metadata, so use them only when that contract is intentional. `Output\DebugOutput` provides diagnostic serialization.

For a runnable validate/save example and explicit boundary mapping, consult the package's v2 quickstart and recipes.

This package requires PHP 8.4 or later.
- Configure debug sanitization explicitly for the application; it is diagnostic output and must not be treated as a public response format.
