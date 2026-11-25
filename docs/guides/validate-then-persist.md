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

## Related pages

[Chaining reference](/reference/chaining) and [API boundary recipe](/recipes/api-boundary-mapping).
