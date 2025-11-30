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

