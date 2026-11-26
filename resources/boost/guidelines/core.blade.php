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

`ResultBatch` provides keyed batch operations. `Retry::config()` and `ResourceScope::run()` capture exceptions; configure retry predicates for transient failures. `Output\Serialization` includes metadata, so map public responses explicitly. `Output\DebugOutput` provides diagnostic serialization.

For a runnable validate/save example and explicit boundary mapping, consult the package's v2 quickstart and recipes.

This package requires PHP 8.4 or later.
- Configure debug sanitization explicitly for the application; it is diagnostic output and must not be treated as a public response format.
- Core Result chains work without Laravel; use framework integration only at the application boundary.
- `ResourceScope::run` releases an acquired resource even when the use callback throws; inspect `bracket.release_exception` if cleanup also fails.
- Fallible callbacks passed to `andThen` and `orElse` must return an actual `Result`; an invalid return raises `TypeError`.
- A null failure is still a failure and skips `andThen`; selective `attempt` capture lets unrelated exceptions propagate unchanged.
- The core remains framework-independent; when using Laravel, follow the integration and transaction behavior of the application's installed Laravel version.
- The default debug sanitizer detects array cycles and limits array depth (`array_max_depth`, default 32); disabled or custom sanitizers must provide their own safeguards. XML requires SimpleXML and rejects invalid UTF-8 or XML 1.0 characters.
- Retry defaults to retrying every failure. Filter permanent failures and use idempotency keys for repeated writes; delays block the current PHP process.
- Applications using PHPStan may opt into `phpstan-result-flow.neon` to flag standalone calls whose inferred return is a Result. Assigning a Result does not itself prove it was handled; use `@phpstan-ignore resultFlow.ignoredResult` for intentional discards.
- `ResultBatch::firstSuccess` selects the first success (or last failure) from non-empty evaluated results; `zip` combines two evaluated results. For validation, return arrays of messages as keyed failures.
- Structured `ResultError` causes appear in diagnostic `error_cause` after sanitization; they are not automatically added to client responses.
- For problem JSON responses, explicitly map domain failures to public fields with `Output\ProblemDetails::response`; it never includes Result metadata automatically.
- A controller can consume an action with `match`, selecting public fields and a status for each outcome. Publish diagnostic settings in a Laravel app with `php artisan vendor:publish --tag=result-flow-config`.
