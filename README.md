# Result Flow

[![run-tests](https://github.com/Maxiviper117/result-flow/actions/workflows/run-tests.yml/badge.svg)](https://github.com/Maxiviper117/result-flow/actions/workflows/run-tests.yml)
[![PHPStan](https://github.com/Maxiviper117/result-flow/actions/workflows/phpstan.yml/badge.svg)](https://github.com/Maxiviper117/result-flow/actions/workflows/phpstan.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Minimal, type-safe Result type for explicit success/failure handling in PHP (PHP 8.2+).

Composer:

```bash
composer require maxiviper117/result-flow
```

Why Result Flow?

- Explicit branches: handle success with `then()` and failures with `otherwise()` (or both with `match()`).
- Metadata travels with the pipeline (correlation IDs, audit context, failed input).
- Exceptions are captured by default but you can opt into `thenUnsafe()` for transactional behavior.
- Built-in helpers: retries, JSON/XML transformers, and Laravel boundary helpers.

Quick links: [Getting Started](https://maxiviper117.github.io/result-flow/getting-started.html) • [API reference](https://maxiviper117.github.io/result-flow/api.html)

## Quick copy-paste examples

Quick pipeline + match:

```php
use Maxiviper117\\ResultFlow\\Result;

$result = Result::ok(['order_id' => 123, 'total' => 42])
    ->then(fn($o) => $o['total'] > 0 ? Result::ok($o) : Result::fail('empty'))
    ->then(fn($o) => Result::ok(['saved' => true, 'id' => $o['order_id']]));

echo $result->match(
    onSuccess: fn($v) => json_encode($v),
    onFailure: fn($e) => json_encode(['error' => (string) $e]),
);
```

Metadata chaining (attach/propagate context):

```php
Result::ok(['id' => 1], ['request_id' => 'r-1'])
    ->mergeMeta(['started_at' => microtime(true)])
    ->then(fn($v, $meta) => Result::ok($v, [...$meta, 'validated' => true]));
```

Exception → Result (wrap a throwing call):

```php
$res = Result::of(fn() => mayThrow())
    ->otherwise(fn($e) => Result::fail('downstream error'));
```

Laravel boundary (`toResponse()` returns a Laravel response when available):

```php
$result = Result::ok(['message' => 'ok']);
$response = $result->toResponse(); // Response instance in Laravel, array fallback otherwise
```

## When to use Result Flow

- Use in controllers, background jobs, HTTP client adapters, or transactional flows where you want explicit success/failure handling and metadata propagation.

## Debugging & observability

- Use `toDebugArray()` to produce a sanitized, debug-friendly shape. Configure sanitization via `config/result-flow.php` in Laravel projects. See the hosted docs for [debugging](https://maxiviper117.github.io/result-flow/debugging.html) and [sanitization](https://maxiviper117.github.io/result-flow/sanitization.html).

## Retries & resiliency

- Use `Result::retry()` for simple retry needs or `Result::retrier()` for advanced configurations (jitter, max attempts, etc.). Read more in the hosted [Retrying Operations](https://maxiviper117.github.io/result-flow/result/retrying.html) guide.
- When you need to know how many attempts ran, call `->attachAttemptMeta()` before `->attempt()` to merge `['retry' => ['attempts' => ...]]` into the returned metadata.

## Interop & migration

- Convert exceptions to `Result` with `Result::of()`. Prefer `then()` for safe chaining and `thenUnsafe()` when you need exceptions to bubble (e.g., DB transactions).

## Contributing & tests

See [CONTRIBUTING.md](CONTRIBUTING.md). Run the test suite with:

```bash
composer test
```

## License

MIT — see `LICENSE.md`.

