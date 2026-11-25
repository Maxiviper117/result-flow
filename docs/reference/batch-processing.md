# Batch helpers

`ResultBatch` preserves keys and merges visited metadata shallowly in iteration order, later results winning collisions. It does not catch callback exceptions. See the [batch guide](/guides/batch-strategy).

- `firstFailure(array $results): Result` combines already-evaluated Results, stopping its inspection at the first failure. Successful output is the keyed values; failure is the first error.
- `allErrors(array $results): Result` inspects every already-evaluated Result. If any fail, returns keyed errors only, with no partial successes; otherwise returns keyed values.
- `mapFirstFailure(array $items, callable $fn): Result` calls `fn($item, $key)` until the first Fail, leaving later callbacks unevaluated.
- `mapAllErrors(array $items, callable $fn): Result` calls every callback and returns all keyed failures or all keyed successes.

```php
$results = ['x' => Result::fail('required'), 'y' => Result::ok(1)];
$combined = ResultBatch::allErrors($results); // Fail(['x' => 'required'])
```

Related: [collecting batch errors](/recipes/collecting-batch-errors).
