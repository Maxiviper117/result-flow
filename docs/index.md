---
title: Result Flow
---

# Result Flow

> A lightweight, type-safe Result monad for explicit success/failure handling in PHP.

Result Flow lets you model success and failure explicitly without scattering exceptions. Wrap values, errors, and metadata in a fluent pipeline that short-circuits correctly, preserves context, and keeps type information intact.

## Why Result Flow?

- Clear branching: `then()` for success, `otherwise()` for failure, `match()` for exhaustive handling.
- Safe pipelines: exceptions are captured as `Result::fail(...)` unless you opt into `thenUnsafe()`.
- Metadata propagation: correlation IDs and context stay attached to every step.
- Strong typing: templates keep PHPStan/Psalm aware of success and error shapes.
- Debug ready: `toDebugArray()` sanitizes sensitive data and respects Laravel config when present.

## Quick Example

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok($order)
    ->then(new ValidateOrder)
    ->ensure(fn($o) => $o->paid, 'Unpaid order')
    ->then(new DispatchOrder)
    ->otherwise(fn($error, $meta) => Result::fail("Could not ship: {$error}", $meta));

return $result->match(
    onSuccess: fn($shipped) => response()->json($shipped),
    onFailure: fn($error)   => response()->json(['error' => $error], 400),
);
```

## Start Here

- Read [Getting Started](/getting-started) for installation and the mental model.
- Dive into the [Result class deep dives](/result/) for constructors, chaining, matching, and debugging walkthroughs.
- Learn about [Sanitization & Safety](/sanitization) for keeping logs clean and secure.
- Browse the [API reference](/api) when you need signatures and return types.
- Check the [FAQ](/faq) for common questions and troubleshooting.
- Check [Usage Patterns](/guides/patterns) and [Anti-Patterns](/guides/anti-patterns) for practical do/do-not guidance.
- Peek into [Internals](/guides/internals) if you want to understand chaining and metadata propagation.
