# Save your first order

Build a command that accepts a customer name, trims whitespace, and saves an order only if the name is present. A blank name is an expected outcome that the caller can handle. The example uses an in-memory save action so you can run both paths without a database.

## Run the example

From a checkout of the v2 branch, use PHP 8.4 or later and Composer:

```sh
composer install
php examples/v2/action.php ' Ada '
php examples/v2/action.php '   '
```

The first command that runs PHP prints `Saved Ada`. The second prints `Failed: Name is required`. Here is the complete file those commands run:

<<< ../examples/v2/action.php

## Follow the two inputs

| Step | Input `' Ada '` | Input `'   '` |
| --- | --- | --- |
| `map` trims the name | Success carrying `'Ada'` | Success carrying `''` |
| `andThen($validate)` checks the name | Success carrying `'Ada'` | Failure carrying the validation message |
| `andThen($save)` builds the order | Success carrying the order array | Save is skipped |
| `match` selects the output | The success callback receives the order | The failure callback receives the message |

An empty string is still a successful value after trimming. The validator makes the business decision to reject it. Keeping that decision in the action lets a CLI print a message while an HTTP controller can return a validation response for the same failure.

## Use it in your application

Once a v2 beta is published, install it with `composer require 'maxiviper117/result-flow:^2.0@beta'`. In your own script, load Composer's `vendor/autoload.php` relative to your project root.

Continue with [validate then persist](/guides/validate-then-persist) to write an action, or [map a result to HTTP](/recipes/api-boundary-mapping) to expose it to clients.
