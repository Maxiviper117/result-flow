# Validate then persist

## What this page is for

Compose a value normalization with validation and persistence actions whose expected errors differ.

## When to use this

Use a chain when each successful step feeds the next and a failure should stop later work.

```php
use Maxiviper117\ResultFlow\Result;

$normalize = fn (string $input): string => trim($input);
$validate = fn (string $name): Result => $name === ''
    ? Result::fail('Name is required') : Result::ok($name);
$save = fn (string $name): Result => Result::ok(['name' => $name]);

$result = Result::ok(' Ada ', ['trace_id' => 't-1'])
    ->map($normalize)
    ->andThen($validate)
    ->andThen($save);
// Ok(['name' => 'Ada']); trace_id remains available in meta().
```

## Choose map vs andThen

| Callback returns | Use | Failure behavior |
| --- | --- | --- |
| A plain value | `map` | Existing Fail skips it |
| A Result | `andThen` | Existing Fail skips it; a returned Fail stops later steps |

Both callbacks receive one argument, and exceptions propagate. `andThen` merges the returned metadata with child keys winning. Use ordinary variables and early returns when they read more clearly.


## Handle persistence failure separately

A unique-name constraint can still reject a valid name. Have the save action return a failure such as `Result::fail('name_taken')` for that expected condition. The caller can now distinguish a blank name (validation failed before saving) from a collision (saving was attempted). For a write involving several database statements, follow the [transaction guide](/guides/transaction-rollback).

## Related pages

[Chaining reference](/reference/chaining) and [API boundary recipe](/recipes/api-boundary-mapping).
