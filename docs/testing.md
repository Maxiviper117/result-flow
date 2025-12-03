---
title: Test Recipes
---

# Test Recipes

## State Assertions

```php
$result = Result::ok('value');
expect($result->isOk())->toBeTrue();
expect($result->isFail())->toBeFalse();
expect($result->value())->toBe('value');
expect($result->error())->toBeNull();
```

```php
$result = Result::fail('error');
expect($result->isFail())->toBeTrue();
expect($result->value())->toBeNull();
expect($result->error())->toBe('error');
```

## Chaining

```php
it('short-circuits on failure', function () {
    $executed = false;

    $result = Result::fail('error')
        ->then(function () use (&$executed) {
            $executed = true;
            return Result::ok('value');
        });

    expect($executed)->toBeFalse();
    expect($result->isFail())->toBeTrue();
});
```

## ensure()

```php
$result = Result::ok(10)
    ->ensure(fn($v) => $v > 5, 'Too small');
expect($result->isOk())->toBeTrue();
```

```php
$result = Result::ok(3)
    ->ensure(fn($v) => $v > 5, 'Too small');
expect($result->isFail())->toBeTrue();
expect($result->error())->toBe('Too small');
```

## combine() vs combineAll()

```php
$combined = Result::combine([
    Result::ok('a'),
    Result::ok('b'),
    Result::ok('c'),
]);
expect($combined->isOk())->toBeTrue();
expect($combined->value())->toBe(['a', 'b', 'c']);
```

```php
$combined = Result::combineAll([
    Result::fail('error1'),
    Result::ok('value'),
    Result::fail('error2'),
]);
expect($combined->isFail())->toBeTrue();
expect($combined->error())->toBe(['error1', 'error2']);
```

## Matching

```php
$out = Result::ok('hello')->match(
    onSuccess: fn($v) => strtoupper($v),
    onFailure: fn($e) => 'ERROR',
);
expect($out)->toBe('HELLO');
```

```php
$out = Result::fail('oops')->match(
    onSuccess: fn($v) => 'SUCCESS',
    onFailure: fn($e) => "Error: {$e}",
);
expect($out)->toBe('Error: oops');
```

## Exception Safety

```php
$result = Result::ok('x')
    ->then(fn() => throw new RuntimeException('boom'));

expect($result->isFail())->toBeTrue();
expect($result->error())->toBeInstanceOf(RuntimeException::class);
expect($result->meta()['failed_step'])->toBe('closure');
```

## Running the Suite

```bash
composer test
```
