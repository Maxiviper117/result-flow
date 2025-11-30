# Result Flow

[![run-tests](https://github.com/Maxiviper117/result-flow/actions/workflows/run-tests.yml/badge.svg)](https://github.com/Maxiviper117/result-flow/actions/workflows/run-tests.yml)
[![PHPStan](https://github.com/Maxiviper117/result-flow/actions/workflows/phpstan.yml/badge.svg)](https://github.com/Maxiviper117/result-flow/actions/workflows/phpstan.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Minimal, type-safe Result monad for explicit success/failure handling in PHP.

> **Primary class:** `Maxiviper117\ResultFlow\Result` (see `src/Result.php`)

## Why

- Keep success and failure paths explicit
- Chain actions fluently with metadata propagation
- Convert exceptions to failures automatically
- Small surface area with PHPStan-friendly templates

## Installation

```bash
composer require maxiviper117/result-flow
```

Import and use:

```php
use Maxiviper117\ResultFlow\Result;
```

## Examples

Standalone scripts (not autoloaded by the package) you can run to explore behaviors:

- `php examples/basic.php` — chaining, ensure/otherwise, debug output
- `php examples/actions.php` — action objects with `handle`/`execute`, `thenUnsafe`, recovery
- `php examples/combine.php` — validating multiple fields with `combine` vs `combineAll`

### Laravel config publish (optional)

The package is framework-agnostic, but if you're using Laravel the service provider is auto-discovered. Publish the config to override debug sanitization settings (redaction token, sensitive keys, max string length):

```bash
php artisan vendor:publish --tag=result-flow-config
```

Edit `config/result-flow.php` to match your policies. Keys include `enabled`, `redaction`, `sensitive_keys`, `max_string_length`, and `truncate_strings`. `Result::toDebugArray()` will pick up these values via the `config()` helper when present.

## Quick Start

```php
$result = Result::ok($payload)
    ->then(new ValidateOrder)              // runs on success
    ->then(fn($data, $meta) => save($data))
    ->otherwise(fn($error, $meta) => cacheFallback($error, $meta));

// Handle both branches
return $result->match(
    onSuccess: fn($value) => response()->json($value),
    onFailure: fn($error) => response()->json(['error' => $error], 400),
);
```

## Documentation

The full guide, API reference, patterns, and testing notes live in [docs/result-guide.md](docs/result-guide.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for setup, checks, and PR expectations.

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

