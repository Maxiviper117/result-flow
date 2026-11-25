# Runnable v2 examples

Run from the repository root after installing Composer dependencies:

- `php examples/v2/action.php`: normalize, validate, and save in an explicit chain.
- `php examples/v2/batch.php`: collect keyed validation failures.
- `php examples/v2/boundary.php`: map an error to a response without diagnostic metadata.
- `php examples/v2/resource.php`: acquire, use, and release a resource.

See the [quickstart](../docs/getting-started.md), and [API reference](../docs/reference/index.md).
- `php -S 127.0.0.1:8000 examples/v2/order-endpoint.php`: run the representative order endpoint; POST a `name`. Its response excludes the internal request ID.
