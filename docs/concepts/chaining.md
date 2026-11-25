# Chaining

`map` transforms an Ok payload into any value, including a nested Result. `andThen` expects a Result and flattens that one step. Neither catches exceptions. Fail skips both callbacks. See the [chaining reference](/reference/chaining) and [validate then persist](/guides/validate-then-persist).
