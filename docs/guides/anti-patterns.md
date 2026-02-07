---
title: Anti-Patterns
---

# Anti-Patterns

## What this page is for

Use this page to avoid common mistakes that make `Result` pipelines confusing or brittle.

## Anti-pattern: Using `map` for Result-returning callbacks

Bad:
```php
Result::ok($data)->map(fn ($v) => Result::ok(transform($v)));
```

Why it is bad:
- Creates nested `Result` values.

Use instead:
```php
Result::ok($data)->then(fn ($v) => Result::ok(transform($v)));
```

## Anti-pattern: Mixing throw and fail strategy in one layer

Bad:
- Some steps return `Result::fail`
- Other steps throw for domain failures

Why it is bad:
- Failure handling becomes unpredictable.

Use instead:
- Choose explicit `Result` failures in domain/services.
- Reserve throws for truly exceptional or boundary-level conditions.

## Anti-pattern: Ignoring metadata

Bad:
```php
Result::ok($payload)->then(fn ($v) => process($v));
```

Why it is bad:
- You lose context opportunities (trace IDs, item keys).

Use instead:
```php
Result::ok($payload, ['request_id' => $rid])
    ->then(fn ($v, array $meta) => process($v, $meta));
```

## Anti-pattern: Wrong batch primitive

- Need per-item status but using `mapAll`.
- Need fail-fast but using `mapCollectErrors`.

Use the decision table in [Batch Processing](/result/batch-processing).

## Anti-pattern: Unwrapping too early

Bad:
```php
$value = doWork()->unwrap();
// more processing here
```

Why it is bad:
- You lose branch control and metadata too early.

Use instead:
- Keep chaining until boundary and then `match`, `toResponse`, or `unwrap*`.

## Related pages

- [Usage Patterns](/guides/patterns)
- [Matching and Unwrapping](/result/matching-unwrapping)
- [API Reference](/api)
