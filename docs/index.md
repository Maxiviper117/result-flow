---
title: Result Flow
---

# Result Flow

Result Flow is a type-safe `Result` implementation for PHP 8.2+.

It gives you one consistent model for success, failure, and metadata:

```text
Ok(value, meta) | Fail(error, meta)
```

## Who this is for

- Application developers who want explicit, testable failure handling.
- Teams replacing ad-hoc exception chains with predictable control flow.
- Projects that need metadata propagation (request IDs, failed input, audit context).

## Start paths

- New to the library: [Getting Started](/getting-started)
- Understand foundations and method families: [Result Guide](/result/)
- Learn composition lanes: [Composition Patterns](/result/compositions)
- Method signatures and contracts: [API Reference](/api)
- Real examples: [Examples](/examples/)

If you are new, start with [Getting Started](/getting-started) and then follow the read-order list there.

## Result concept lanes

- Foundations: [Result Overview](/result/), [Constructing Results](/result/constructing), [Chaining and Transforming](/result/chaining)
- Compositions: [Core Pipelines](/result/compositions/core-pipelines), [Failure and Recovery](/result/compositions/failure-recovery), [Finalization Boundaries](/result/compositions/finalization-boundaries), [Metadata and Observability](/result/compositions/metadata-observability)
- Operational flows: [Error Handling](/result/error-handling), [Retrying](/result/retrying), [Batch Processing](/result/batch-processing)
- Boundaries and output: [Matching and Unwrapping](/result/matching-unwrapping), [Transformers](/result/transformers), [Metadata and Debugging](/result/metadata-debugging)

## Example concept lanes

Plain PHP first if you are not using Laravel. Choose Laravel examples only when integrating with framework boundaries.

- Core pipelines: [Plain PHP Basics](/examples/plain-php-basics), [Laravel Workflow](/examples/laravel), [Laravel Actions Pipeline](/examples/laravel-actions-pipeline)
- Failure and recovery: [Plain PHP Error Handling](/examples/plain-php-errors), [Laravel Retries](/examples/laravel-retries), [Laravel Actions Exceptions](/examples/laravel-actions-exceptions)
- Collections and combining: [Plain PHP Batch Processing](/examples/plain-php-batch), [Laravel Combine](/examples/laravel-combine)
- Boundaries: [Laravel Controller-only](/examples/laravel-controller-only), [Laravel Match + Unwrap](/examples/laravel-match-unwrap), [Laravel Transactions](/examples/laravel-transactions)
- Observability: [Laravel Debugging](/examples/laravel-debugging), [Laravel Metadata + Taps](/examples/laravel-meta-taps)

## Practical references

- Laravel Boost integration: [Laravel Boost](/laravel-boost)
- [Usage Patterns](/guides/patterns)
- [Anti-Patterns](/guides/anti-patterns)
- [Testing Recipes](/testing)
- [Sanitization Guide](/sanitization)
- [FAQ](/faq)
