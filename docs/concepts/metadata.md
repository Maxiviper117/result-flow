# Metadata

`meta()` returns a string-keyed diagnostic bag. `withMeta($bag)` replaces it on a new Result. `map` and `mapError` preserve the bag. When `andThen` or `orElse` runs, it shallow-merges parent and returned bags: child keys win, even when the child value is null; nested arrays are replaced, not merged. An empty child bag does not delete parent keys. Call `withMeta([])` on the final result to clear it.

Do not rely on metadata for audit data that must not be overwritten, and do not expose it to clients without an explicit allowlist. See [metadata reference](/reference/metadata-debugging).

## Follow a request through two actions

```php
use Maxiviper117\ResultFlow\Result;

$validated = Result::ok(42, ['trace' => 'req-7', 'step' => ['validate' => true]]);
$saved = $validated->andThen(fn (int $id): Result =>
    Result::ok($id, ['step' => ['save' => true]])
);

$saved->meta();     // ['trace' => 'req-7', 'step' => ['save' => true]]
$validated->meta(); // ['trace' => 'req-7', 'step' => ['validate' => true]]
```

The trace survives because the save action supplied no replacement for that key. The entire nested `step` array changes because both actions supplied that key. Give independent measurements distinct top-level keys if both must survive.
