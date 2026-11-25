# Failure handling

`mapError` changes the failure payload without recovering. `orElse` invokes a failure callback returning a replacement Result. Both skip Ok and propagate thrown exceptions. Use `attempt` at a boundary to capture a selected Throwable class; expected business failures should be returned directly. See the [failure reference](/reference/failure-handling).
