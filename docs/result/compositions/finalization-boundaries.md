---
title: Finalization Boundaries
---

# Finalization Boundaries

_Reading time: ~6 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Overview

This page explains where to end a Result pipeline and which boundary method to choose:

```text
match vs unwrap* vs toResponse
```

## Default behavior

- `match` is exhaustive and non-throwing by default.
- `unwrap` / `unwrapOr` / `unwrapOrElse` / `getOrThrow` extract values for non-Result callers.
- `throwIfFail` escalates failures to exceptions while preserving success branch values.
- `toResponse` converts branch state to HTTP-compatible output at framework edges.

## When to use

- `match`: return structured output in domain/service layers.
- `unwrap*`: interop with APIs expecting plain values.
- `throwIfFail` + `thenUnsafe`: transaction rollback semantics.
- `toResponse`: controller boundary in Laravel or normalized response output outside Laravel.

## When not to use

- Avoid `unwrap` in inner pipeline steps where failure should remain explicit.
- Avoid `toResponse` deep in service layers; keep boundary conversion at the edge.
- Avoid mixing multiple boundary styles in one function unless intentionally bridging layers.

## Composes with

- [`match`](/api#match-callable-onsuccess-callable-onfailure-mixed)
- [`matchException`](/api#matchexception-array-exceptionhandlers-callable-onsuccess-callable-onunhandled-mixed)
- [`unwrap*`](/api#matching-and-unwrapping)
- [`throwIfFail`](/api#throwiffail-result)
- [`toResponse`](/api#toresponse-mixed)

## Example progression

### Minimal snippet

```php
$payload = $result->match(
    onSuccess: fn ($value) => ['ok' => true, 'data' => $value],
    onFailure: fn ($error) => ['ok' => false, 'error' => (string) $error],
);
```

### Production-shaped snippet

```php
use Illuminate\Support\Facades\DB;
use Maxiviper117\ResultFlow\Result;

$transactionResult = DB::transaction(function () use ($dto): Result {
    return Result::ok($dto)
        ->thenUnsafe(new ValidateOrderAction)
        ->throwIfFail()
        ->thenUnsafe(new PersistOrderAction)
        ->thenUnsafe(new ChargePaymentAction);
});

return $transactionResult
    ->otherwise(fn ($error, array $meta) => Result::fail([
        'message' => (string) $error,
        'code' => 'CHECKOUT_FAILED',
    ], $meta))
    ->toResponse();
```

Try it:
- See [Laravel Match + Unwrap Example](/examples/laravel-match-unwrap)

## Failure modes and edge cases

- `unwrap` throws on failure; never use it where failures are expected business outcomes.
- `throwIfFail` throws `RuntimeException` for non-Throwable errors.
- `toResponse` may leak raw error details if failures were not normalized first.

## Related API entries

- [Matching and unwrapping](/api#matching-and-unwrapping)
- [Output transformers](/api#output-transformers)
- [Failure branch handlers](/api#failure-branch-handlers)
