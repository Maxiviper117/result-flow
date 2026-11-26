# Chaining and recovery

| Method | Callback | Active branch |
| --- | --- | --- |
| `map` | Value to value | Ok |
| `andThen` | Value to Result | Ok |
| `mapError` | Error to error | Fail |
| `orElse` | Error to Result | Fail |

Callbacks receive one argument and exceptions propagate. See [chaining](/reference/chaining) and [failure handling](/reference/failure-handling).

For a cached price lookup, use `$cached->orElse($loadFromStore)->map($formatPrice)`. On a hit, formatting receives the cached price. On a miss, the fallback receives the cache error and returns a Result. If the fallback also fails, formatting is skipped. See [failure handling](/concepts/failure-handling) for a worked recovery.
