# Result Flow

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

The full guide, API reference, patterns, and testing notes live in `docs/result-guide.md`.

## Testing

```bash
composer test
```
