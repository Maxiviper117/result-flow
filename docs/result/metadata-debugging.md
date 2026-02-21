---
title: Metadata and Debugging
---

# Metadata and Debugging

_Reading time: ~5 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Task summary

Use metadata and tap methods to preserve observability context and produce safe diagnostics.

Deep dives:
- Metadata patterns: [Metadata and Observability](/result/compositions/metadata-observability)
- Pipeline composition: [Core Pipelines](/result/compositions/core-pipelines)
- Contracts: [API Reference](/api#metadata-operations)

## Quick mental model

- Metadata exists on both branches.
- `mergeMeta` adds fields, `mapMeta` replaces full metadata.
- `tap*` methods are side-effect only (no branch change).
- `toDebugArray` is the safe diagnostics output.

## Primary methods

- `meta`, `mergeMeta`, `mapMeta`, `tapMeta` for metadata discipline.
- `tap`, `inspect`, `inspectError` for side-effect inspection.
- `toDebugArray` for sanitized debug output.
- `toArray` for raw shape.

## When to use `mergeMeta` vs `mapMeta` vs `toDebugArray`

| Need | Method |
|---|---|
| Add/overwrite a few metadata keys | `mergeMeta` |
| Replace metadata shape completely | `mapMeta` |
| Produce log-safe output | `toDebugArray` |

## Worked flow (end-to-end)

### Input

```php
$payload = ['email' => 'dev@example.com'];
```

### Flow steps

1. Start success with correlation metadata.
2. Add operation metadata and inspect failure path.
3. Render safe debug output.

### Output

- Success/failure with preserved metadata.
- Sanitized debug-friendly array sample:

```php
[
  'ok' => true,
  'value' => ['email' => 'dev@example.com'],
  'error' => null,
  'meta' => ['request_id' => 'r-400', 'operation' => 'user.normalize'],
]
```

## Copy-paste snippet

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$payload = ['email' => 'dev@example.com'];

$result = Result::ok($payload, ['request_id' => 'r-400'])
    ->mergeMeta(['operation' => 'user.normalize'])
    ->tapMeta(fn (array $meta) => print_r(['meta' => $meta]))
    ->ensure(fn (array $value): bool => isset($value['email']), 'Missing email')
    ->inspectError(fn (mixed $error, array $meta) => print_r(['error' => $error, 'meta' => $meta]));

$debug = $result->toDebugArray();
print_r($debug);
```

## Failure demo

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$result = Result::fail('Validation failed', ['token' => 'secret-token']);
print_r($result->toArray());
print_r($result->toDebugArray());
```

Expected behavior: `toArray` shows raw metadata; `toDebugArray` applies sanitization rules.

## Common beginner mistakes

- Logging `toArray()` in untrusted channels (not sanitized).
- Replacing all metadata with `mapMeta` accidentally.
- Mutating metadata keys inconsistently across steps.
- Assuming tap methods transform branch values.

## Try it

- `php examples\debug\debug-sanitization-demo.php`
- `php examples\retry\retry-defer-test.php`

## Related pages

- [Sanitization Guide](/sanitization)
- [Transformers](/result/transformers)
- [API Reference](/api)
