# Chaining and recovery

| Method | Callback | Active branch |
| --- | --- | --- |
| `map` | Value to value | Ok |
| `andThen` | Value to Result | Ok |
| `mapError` | Error to error | Fail |
| `orElse` | Error to Result | Fail |

Callbacks receive one argument and exceptions propagate. See [chaining](/reference/chaining) and [failure handling](/reference/failure-handling).
