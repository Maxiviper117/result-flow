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

`firstSuccess(array $results)` returns the first success or the last failure, merging metadata from visited Results. Empty input throws `InvalidArgumentException`. `zip($first, $second)` combines two already-evaluated Results into a pair, or returns the first failure; metadata follows ordinary `andThen` semantics.

## Predict the result

Given the inputs below, choose the operation by the answer you need. These snippets use `Maxiviper117\ResultFlow\Result` and `Maxiviper117\ResultFlow\ResultBatch`.

```php
$checks = [
    'name' => Result::fail('Required'),
    'age' => Result::ok(30),
    'email' => Result::fail('Invalid address'),
];
```

| Call | Outcome |
| --- | --- |
| `ResultBatch::firstFailure($checks)` | Fail carrying `'Required'` |
| `ResultBatch::allErrors($checks)` | Fail carrying `['name' => 'Required', 'email' => 'Invalid address']` |
| `ResultBatch::firstSuccess($checks)` | Ok carrying `30` |
| `ResultBatch::zip(Result::ok('Ada'), Result::ok(30))` | Ok carrying the positional pair `['Ada', 30]` |

Building an array of Results runs the expressions that produce them before aggregation starts. If a validation failure should prevent later work from running, pass raw inputs and a callback instead:

```php
$check = fn (string $name, string $key): Result => trim($name) === ''
    ? Result::fail("Missing $key")
    : Result::ok(trim($name));
$names = ['buyer' => '', 'seller' => ' Ada '];

ResultBatch::mapFirstFailure($names, $check); // Fail('Missing buyer'), seller skipped
ResultBatch::mapAllErrors($names, $check);    // Fail(['buyer' => 'Missing buyer']), seller checked
```
