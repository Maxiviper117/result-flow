---
title: Testing Recipes
---

# Testing Recipes

## What this page is for

Use these checks to test branch behavior, metadata propagation, and batch semantics.

## Assert success/failure branch

```php
expect($result->isOk())->toBeTrue();
expect($result->value())->toBe($expected);

expect($result->isFail())->toBeTrue();
expect($result->error())->toBe('expected-error');
```

## Assert metadata propagation

```php
$result = Result::ok($dto, ['request_id' => 'r-1'])
    ->mergeMeta(['step' => 'validated']);

expect($result->meta())->toMatchArray([
    'request_id' => 'r-1',
    'step' => 'validated',
]);
```

## Assert fail-fast behavior

```php
$visited = [];

Result::mapAll(['a' => 1, 'b' => 2], function ($item, $key) use (&$visited) {
    $visited[] = $key;

    return $item === 2 ? Result::fail('bad') : Result::ok($item);
});

expect($visited)->toBe(['a', 'b']);
```

## Assert collect-all behavior

```php
$result = Result::mapCollectErrors(['a' => 1, 'b' => 2, 'c' => 3], function ($item, $key) {
    return $item % 2 === 0 ? Result::ok($item) : Result::fail("bad-{$key}");
});

expect($result->isFail())->toBeTrue();
expect($result->error())->toBe(['a' => 'bad-a', 'c' => 'bad-c']);
```

## Related pages

- [Batch Processing](/result/batch-processing)
- [Metadata and Debugging](/result/metadata-debugging)
