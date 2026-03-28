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
- [Concepts](https://maxiviper117.github.io/result-flow/concepts/)
- [Guides](https://maxiviper117.github.io/result-flow/guides/)
- [Recipes](https://maxiviper117.github.io/result-flow/recipes/)
- [Reference](https://maxiviper117.github.io/result-flow/reference/)
- [Kitchen sink](https://maxiviper117.github.io/result-flow/kitchen-sink/)
- [FAQ](https://maxiviper117.github.io/result-flow/faq.html)
- [Laravel Boost](https://maxiviper117.github.io/result-flow/laravel-boost.html)

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

## Deferred operations

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::defer(fn () => loadUserById($id))
    ->then(fn (array $user) => Result::ok(normalizeUser($user)));
```

## Retry deferred operations

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::retryDefer(
    3,
    fn () => callExternalApi($payload),
    delay: 100,
    exponential: true,
);
```

## Resource-safe operations

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::bracket(
    acquire: fn () => fopen($path, 'r'),
    use: fn ($handle) => fread($handle, 100),
    release: fn ($handle) => fclose($handle),
);
```

## Batch workflows

- `Result::mapItems($items, $fn)` for per-item `Result` status.
- `Result::mapAll($items, $fn)` for fail-fast aggregate processing.
- `Result::mapCollectErrors($items, $fn)` for collect-all error reporting.

All batch callbacks use: `fn ($item, $key) => Result|value`.

## Structured domain errors

For domain-level failures that need a stable API shape and explicit branching, use
subclasses of `DataTaggedError` and match them by class with `matchError(...)`
or `catchError(...)`.

```php
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\DataTaggedError;

final class UserPersistError extends DataTaggedError
{
    public const CODE = 'E_USER_PERSIST';
}

$result = Result::fail(UserPersistError::from(
    'Unable to persist user',
    ['email' => 'dev@example.com'],
));

$message = $result->matchError(
    [UserPersistError::class => fn (UserPersistError $e) => $e->code()],
    onSuccess: fn ($value) => 'ok',
    onUnhandled: fn ($error) => 'unhandled',
);
```

Use `code()` for boundary serialization and external systems. Matching is based on
the error class, not the string code.

## Laravel Boost

This package ships Laravel Boost source assets so AI agents in downstream consumer apps can generate ResultFlow-aware code.

### Package-shipped guideline source

- Source file in this package: `resources/boost/guidelines/core.blade.php`
- In a Laravel app that uses Boost, run:

```bash
php artisan boost:install
```

Boost applies package-shipped guidance within the app AI context.

### Package-shipped central skill source

- Source file in this package:
  - `resources/boost/skills/result-flow/SKILL.md`
- The central skill loads only needed concept references from:
  - `resources/boost/skills/result-flow/references/*.md`

### App-level overrides

App teams can define or override guidelines locally:

- `.ai/guidelines/...`

To override a built-in guideline, use the same relative path in `.ai/guidelines`, for example:

- `.ai/guidelines/inertia-react/2/forms.blade.php`

## Contributing

- Tests: `composer test`
- Static analysis: `composer analyse`
- Rector check: `composer rector-dry`
- Rector apply: `composer rector`
- Format: `composer format`

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT - see `LICENSE.md`.
