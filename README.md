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

### Laravel config publish (optional)

The package is framework-agnostic, but if you're using Laravel the service provider is auto-discovered. Publish the config to override debug sanitization settings (redaction token, sensitive keys, max string length):

```bash
php artisan vendor:publish --tag=result-flow-config
```

Edit `config/result-flow.php` to match your policies. The `sensitive_keys` option supports glob-style patterns (e.g., `*token`, `api_*`, `?id`) and is case-insensitive. `Result::toDebugArray()` will pick up these values via the `config()` helper when present.

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

The full guide, API reference, patterns, and testing notes are available at [https://maxiviper117.github.io/result-flow/result/](https://maxiviper117.github.io/result-flow/result/).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for setup, checks, and PR expectations.

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

