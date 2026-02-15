# Result Flow

[![run-tests](https://github.com/Maxiviper117/result-flow/actions/workflows/run-tests.yml/badge.svg)](https://github.com/Maxiviper117/result-flow/actions/workflows/run-tests.yml)
[![PHPStan](https://github.com/Maxiviper117/result-flow/actions/workflows/phpstan.yml/badge.svg)](https://github.com/Maxiviper117/result-flow/actions/workflows/phpstan.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/Maxiviper117/result-flow)

Minimal, type-safe Result type for explicit success/failure handling in PHP (PHP 8.2+).

Composer:

```bash
composer require maxiviper117/result-flow
```

## Documentation map

- [Home](https://maxiviper117.github.io/result-flow/)
- [Getting Started](https://maxiviper117.github.io/result-flow/getting-started.html)
- [Result Guide](https://maxiviper117.github.io/result-flow/result/)
- [Batch Processing](https://maxiviper117.github.io/result-flow/result/batch-processing.html)
- [API Reference](https://maxiviper117.github.io/result-flow/api.html)
- [Examples](https://maxiviper117.github.io/result-flow/examples/)

## Why Result Flow

- Explicit branches: success and failure are handled intentionally.
- Metadata propagation: context survives across every chain step.
- Predictable semantics: fail-fast and collect-all tools are explicit and separate.
- Type-aware design: PHPStan-friendly templates across public methods.

## Quick example

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['order_id' => 123, 'total' => 42], ['request_id' => 'r-1'])
    ->ensure(fn (array $order) => $order['total'] > 0, 'Order total must be positive')
    ->then(fn (array $order) => Result::ok([
        'saved' => true,
        'id' => $order['order_id'],
    ]));

$output = $result->match(
    onSuccess: fn (array $v) => ['ok' => true, 'data' => $v],
    onFailure: fn ($e) => ['ok' => false, 'error' => (string) $e],
);
```

## Batch workflows

- `Result::mapItems($items, $fn)` for per-item `Result` status.
- `Result::mapAll($items, $fn)` for fail-fast aggregate processing.
- `Result::mapCollectErrors($items, $fn)` for collect-all error reporting.

All batch callbacks use: `fn ($item, $key) => Result|value`.

## Laravel Boost

This package ships Laravel Boost assets so app teams get ResultFlow-aware guidance automatically.

### Package-provided guidelines

- File location in this package: `resources/boost/guidelines/core.blade.php`
- In a Laravel app that uses Boost, run:

```bash
php artisan boost:install
```

Boost will include the package guideline in the app AI context.

### Package-provided skills

- Skill locations in this package:
  - `resources/boost/skills/result-flow-laravel/SKILL.md`
  - `resources/boost/skills/result-flow-debugging/SKILL.md`
- Install skills in the app using Boost skill install commands, which materialize files in the app `.ai` directory.

### App-level overrides

App teams can define or override guidelines locally:

- `.ai/guidelines/...`

To override a built-in Boost guideline, use the same relative path in `.ai/guidelines`, for example:

- `.ai/guidelines/inertia-react/2/forms.blade.php`

## Contributing

- Tests: `composer test`
- Static analysis: `composer analyse`
- Format: `composer format`

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT - see `LICENSE.md`.
