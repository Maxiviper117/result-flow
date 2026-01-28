---
title: FAQ
---

# FAQ

## What PHP versions are supported?

Result Flow targets PHP 8.2+. If you need older PHP support, you’ll need to pin a compatible release.

## When should I use Result instead of exceptions?

Use `Result` when you want explicit, typed success/failure handling in normal control flow (validation, fallbacks, recoverable errors). Use exceptions when a failure is truly exceptional or you need stack unwinding. You can also combine both: use `Result::of()` or `then()` to convert exceptions into `Result::fail()`.

## How does retry/backoff work?

- `Result::retry()` provides a simple loop with optional delay and exponential backoff.
- `Result::retrier()` lets you add jitter, conditional retry predicates, and `onRetry()` hooks.

See [Retrying Operations](/result/retrying) for examples and detailed behavior.

## Does `toJson()` throw exceptions?

Yes. Internally it uses `json_encode(..., JSON_THROW_ON_ERROR)`, so it can throw `JsonException` if encoding fails. Wrap it if needed:

```php
try {
    echo $result->toJson(JSON_PRETTY_PRINT);
} catch (JsonException $e) {
    // handle encoding issues
}
```

## What does `toDebugArray()` include for errors?

`toDebugArray()` always includes the error type, but the `error_message` field is only set for:

- `Throwable` errors (uses the exception message)
- string errors

Non-string error payloads will produce a `null` `error_message`. Use `error_type` and `meta` for context.

## How do I customize sanitization?

Pass a sanitizer function to `toDebugArray()` or configure Laravel’s `config('result-flow.debug')` values. Sensitive keys are redacted (supports `*` and `?` globs), and long strings are truncated by default.

See [Sanitization & Safety](/sanitization) for details.

## Does Laravel integration require any setup?

The service provider is auto-discovered. If you want to publish config for sanitization settings, run:

```bash
php artisan vendor:publish --tag=result-flow-config
```

The `toResponse()` helper will return a `JsonResponse` when Laravel’s `response()` helper is available.
