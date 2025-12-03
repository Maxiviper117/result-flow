---
title: Anti-Patterns
---

# Anti-Patterns

Avoid these pitfalls to keep pipelines predictable.

## Ignoring Failure

**Wrong**

```php
$value = $result->value(); // might be null
doSomething($value);
```

**Prefer**

```php
$result->match(
    onSuccess: fn($v) => doSomething($v),
    onFailure: fn($e) => handleError($e),
);
```

## Over-Broad Recovery

**Wrong**

```php
->otherwise(fn() => Result::ok($default)) // hides all errors
```

**Prefer**

```php
->otherwise(function ($error) use ($default) {
    if ($error instanceof NotFoundError) {
        return Result::ok($default);
    }
    return Result::fail($error); // propagate others
})
```

## Returning Ambiguous Nulls

**Wrong**

```php
->then(fn($data) => invalid($data) ? null : process($data));
```

**Prefer**

```php
->then(function ($data) {
    if (invalid($data)) {
        return Result::fail('Invalid data');
    }
    return Result::ok(process($data));
});
```

## Losing Metadata

**Wrong**

```php
->then(fn($value, $meta) => Result::ok(transform($value))); // meta dropped
```

**Prefer**

```php
->then(fn($value, $meta) => Result::ok(transform($value), $meta));
```

## Mutating Meta In-Place

**Wrong**

```php
->then(function ($value, $meta) {
    $meta['events'][] = 'processed'; // local only, not persisted
    return $value;
});
```

**Prefer**

```php
->then(function ($value, $meta) {
    $meta['events'][] = 'processed';
    return Result::ok($value, $meta);
});
```

## Wrong Handler Order

When using `catchException()` or `matchException()`, order handlers from most specific to least specific so the parent class does not shadow children.

```php
->catchException([
    \InvalidArgumentException::class => fn($e) => Result::fail('validation'),
    \RuntimeException::class => fn($e) => Result::fail('runtime'),
    \Exception::class => fn($e) => Result::fail('generic'),
]);
```

## Using `combine()` When You Need All Errors

`combine()` stops at the first failure. For validations where you want the full list, use `combineAll()` instead.
