# Detecting discarded Results

## What this page is for

Use the optional PHPStan rule to flag standalone function, method, and static-method calls whose inferred return type is a Result and whose value is discarded. PHPStan's built-in unused-result checks focus on calls without side effects; a Result-returning action can have side effects and still contain an important failure.

## When to use this

Install PHPStan as a development dependency and include the rule only in projects that want this diagnostic:

```yaml
includes:
    - vendor/maxiviper117/result-flow/phpstan-result-flow.neon
```

```php
saveOrder(); // Reported if PHPStan infers Result as the return type.
$result = saveOrder(); // Not reported: the value is assigned.

// @phpstan-ignore resultFlow.ignoredResult
saveOrder(); // Explicit intentional discard.
```

The rule is an opt-in heuristic, not proof that assigned Results are handled. It does not report forwarding, return statements, arguments, or calls whose inferred type is not definitely a Result (such as `Result|null`). Use scoped PHPStan suppression only when ignoring an outcome is intentional. It has no effect on core execution or runtime dependencies.

## Related pages

[Failure handling](/reference/failure-handling) and the [validate and persist example](/guides/validate-then-persist).
