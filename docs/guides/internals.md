---
title: Internals
---

# Internals

## Chain Execution

`then()` and `otherwise()` both delegate to `Support/ResultPipeline::run()`:

1. Normalize the next step(s) into an array (single callable/object stays as one step).
2. Invoke each step with `(currentValue, meta)`.
3. If the step throws, return `Result::fail($exception, array_merge($meta, ['failed_step' => stepName]))`.
4. If the step returns a `Result`, propagate its metadata to subsequent steps. Short-circuit on failure.
5. If the step returns a raw value, wrap it as `Result::ok($value, $meta)`.

## Step Resolution Priority

`Support/ResultPipeline::invokeStep()` chooses how to call the step:

1. `is_callable($step)` → `$step($arg, $meta)` (includes closures and `__invoke`).
2. `handle($arg, $meta)` if it exists.
3. `execute($arg, $meta)` if it exists.
4. Otherwise an `InvalidArgumentException` is thrown.

If an object implements both `__invoke()` and `handle()`, the `__invoke()` path wins because it is callable.

## Exception Strategy

- `then()` wraps steps in try/catch; exceptions become failures with `failed_step` recorded.
- `thenUnsafe()` skips the try/catch and lets exceptions bubble. Combine with `throwIfFail()` when you want `Result::fail()` to escalate to an exception (e.g., for transaction rollbacks).

## Metadata Propagation

When a step returns a `Result` with updated metadata, the updated meta is passed into the next step. This enables pipelines that accumulate context:

```php
Result::ok($payload, ['id' => $id])
    ->then(fn($v, $m) => Result::ok($v, [...$m, 'validated' => true]))
    ->then(fn($v, $m) => Result::ok($v, [...$m, 'saved' => true]));
// final meta contains both validated and saved flags
```

## Type Safety

The class is annotated with PHPStan/Psalm templates:

```php
/**
 * @template TSuccess
 * @template TFailure
 */
final class Result { ... }
```

Common transformations:

| Method | Input | Output |
| --- | --- | --- |
| `map(fn($v) => U)` | `Result<T, E>` | `Result<U, E>` |
| `mapError(fn($e) => F)` | `Result<T, E>` | `Result<T, F>` |
| `ensure(pred, err)` | `Result<T, E>` | `Result<T, E>` |
| `then(fn)` / `flatMap(fn)` | `Result<T, E>` | `Result<U, E>` |
| `otherwise(fn)` | `Result<T, E>` | `Result<T, F>` |
| `recover(fn)` | `Result<T, E>` | `Result<T|U, never>` |
| `combine([...])` | `array<Result<T, E>>` | `Result<array<T>, E>` |
| `combineAll([...])` | `array<Result<T, E>>` | `Result<array<T>, array<E>>` |

## Debugging Defaults

`toDebugArray()` uses a built-in sanitizer that:

- Redacts values whose keys match configured sensitive patterns (supports glob patterns `*` and `?`; plain words remain substring matches) such as `password`, `token`, `api_key`, `ssn`, `card`, etc.
- Optionally truncates long strings (`max_string_length`, default 200).
- Accepts a custom sanitizer callable to override the defaults.
- Reads overrides from Laravel's `config('result-flow.debug')` when available.
