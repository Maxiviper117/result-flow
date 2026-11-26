# Construction

| Method | Purpose |
| --- | --- |
| `ok` | Create a tagged success |
| `fail` | Create a tagged failure |
| `attempt` | Capture a selected Throwable from an immediately executed callback |

See [construction reference](/reference/construction) for signatures and examples.

For example, `Result::ok(0)` is a successful count of zero. Use `Result::fail('count_unavailable')` when counting could not finish. When parsing input with PHP's JSON decoder, use `Result::attempt(fn () => json_decode($json, flags: JSON_THROW_ON_ERROR), catch: JsonException::class)` to preserve the parser exception for the caller.
