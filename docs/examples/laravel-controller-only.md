---
title: Laravel Controller-only
---

# Laravel Controller-only

## Scenario

Keep all Result handling in a controller action without a separate action class.

## Example

```php
public function store(Request $request)
{
    $result = Result::ok($request->validate([
        'email' => ['required', 'email'],
        'name' => ['required', 'string'],
    ]))
        ->then(fn (array $data) => Result::ok(User::create($data)))
        ->map(fn (User $user) => ['id' => $user->id, 'email' => $user->email]);

    return $result->toResponse();
}
```

## Expected behavior

- Controller keeps clear success/failure flow.
- Domain result shape is explicit before HTTP conversion.

## Related pages

- [Chaining and Transforming](/result/chaining)
- [Transformers](/result/transformers)
