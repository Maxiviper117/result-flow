# API reference

`Maxiviper117\ResultFlow\Result` is synchronous. Its type parameters describe the active payload a caller may receive: `T` for success and `E` for failure. For example, PHPStan reads `Result<int, string>` as an integer on success or a string on failure.

| Core methods | Contract |
| --- | --- |
| `ok`, `fail`, `attempt` | [Construction and exception capture](/reference/construction) |
| `map`, `andThen` | [Transforming a success](/reference/chaining) |
| `mapError`, `orElse` | [Transforming or recovering a failure](/reference/failure-handling) |
| `isOk`, `isFail`, `match`, `unwrap`, `unwrapOr` | [Inspection and consumption](/reference/boundaries) |
| `meta`, `withMeta` | [Reading and replacing metadata](/reference/metadata-debugging) |

For collections, see [ResultBatch](/reference/batch-processing). For output, choose between [serialization and HTTP problem details](/reference/boundaries#explicit-adapters) and [sanitized diagnostics](/reference/metadata-debugging#debug-output). [Retries](/guides/retries) and [resource scopes](/recipes/resource-cleanup) own their own callback and exception policies.
