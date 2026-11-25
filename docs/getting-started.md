# Getting started

After a v2 beta is published, install it on PHP 8.4 or later. The explicit stability flag prevents the quickstart from silently selecting stable v1:

```sh
composer require 'maxiviper117/result-flow:^2.0@beta'
```

An action returns an explicit success or failure. Map a value, chain a fallible action, then handle either outcome:

```php
use Maxiviper117\ResultFlow\Result;

$validate = fn (int $n): Result => $n > 0
    ? Result::ok($n)
    : Result::fail('Must be positive');

$message = Result::ok(' 42 ')
    ->map(fn (string $input): int => (int) trim($input))
    ->andThen($validate)
    ->match(
        onSuccess: fn (int $value): string => "Accepted: $value",
        onFailure: fn (string $error): string => "Rejected: $error",
    );
// Accepted: 42
```

Use `map` when the callback returns a value; use `andThen` when it returns a Result. A failure skips the rest of the success chain. Next, see [validate then persist](/guides/validate-then-persist) and the [API reference](/reference/).
