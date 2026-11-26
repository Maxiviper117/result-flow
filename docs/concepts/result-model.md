# Result model

`Result::ok($value)` and `Result::fail($error)` use an explicit branch tag. Both payloads may be null, false, zero, an empty string, or an empty array. `isOk()` and `isFail()` inspect the tag; use `match` to consume the active payload.

Results and their metadata arrays are shallowly immutable: replacing the bag returns another Result, while objects inside it can still be mutable. See [construction](/reference/construction) and [boundaries](/reference/boundaries).

## Represent an optional value

A successful search can find zero matches. A failed search means the caller could not obtain an answer. Preserve both meanings even if your application represents a missing match with null.

```php
use Maxiviper117\ResultFlow\Result;

$describe = fn (Result $lookup): string => $lookup->match(
    onSuccess: fn (?string $name): string => $name ?? 'No match',
    onFailure: fn (string $reason): string => "Lookup failed: $reason",
);

echo $describe(Result::ok(null));        // No match
echo $describe(Result::fail('offline')); // Lookup failed: offline
```

This distinction matters when choosing a fallback. `unwrapOr('Guest')` on a successful null returns null. Use `match` if both a missing match and a failure require their own response.
