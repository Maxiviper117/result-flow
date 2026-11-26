# Recipes

Each recipe starts with an input and shows what the caller receives. Pick one according to who owns the next decision.

| Task | Input | Outcome |
| --- | --- | --- |
| [Map an HTTP response](/recipes/api-boundary-mapping) | A domain success or validation failure | A status and public body with internal trace metadata excluded |
| [Log a failed action](/recipes/logging-failures) | A Result and an application logger | A chosen severity and selected diagnostic fields |
| [Collect validation errors](/recipes/collecting-batch-errors) | A keyed set of submitted fields | All valid values, or an error bag keyed by invalid field |
| [Release a resource](/recipes/resource-cleanup) | An acquire callback and work to perform | A Result after the acquired resource has been released |

For operations that can fail after writing to a database, see [transaction rollback](/guides/transaction-rollback).
