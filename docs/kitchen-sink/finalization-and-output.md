# Finalization and output

| Method | Purpose |
| --- | --- |
| `match` | Handle exactly one active payload |
| `unwrap` | Return a value or throw the failure |
| `unwrapOr` | Return a value or eager default |

`Output\Serialization` and `Output\DebugOutput` are explicit adapters. See [boundary reference](/reference/boundaries).

| Destination | Choose | What leaves the chain |
| --- | --- | --- |
| Controller | `match` | The status and public fields you choose |
| Log | `DebugOutput::toArray` | Diagnostic fields processed by the configured sanitizer |
| Stored interchange data | `Serialization::toJson` | The serialized Result, including metadata |
| HTTP problem response | `ProblemDetails::response` | Explicit problem fields encoded as JSON |
