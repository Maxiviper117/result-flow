# Result model

`Result::ok($value)` and `Result::fail($error)` use an explicit branch tag. Both payloads may be null, false, zero, an empty string, or an empty array. `isOk()` and `isFail()` inspect the tag; use `match` to consume the active payload.

Results and their metadata arrays are shallowly immutable: replacing the bag returns another Result, while objects inside it can still be mutable. See [construction](/reference/construction) and [boundaries](/reference/boundaries).
