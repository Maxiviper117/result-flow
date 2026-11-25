# Failure handling

`mapError` changes the failure payload without recovering. `orElse` invokes a failure callback returning a replacement Result. Both skip Ok and propagate thrown exceptions. Use `attempt` at a boundary to capture a selected Throwable class; expected business failures should be returned directly. See the [failure reference](/reference/failure-handling).

To retain a cause when normalizing an error, do so explicitly. For a structured error, build a new `DataTaggedError` with a `Cause` describing the original error and its nested causes. `DebugOutput::toArray` includes the sanitized chain under `error_cause`; a recovery callback otherwise replaces the original error.
