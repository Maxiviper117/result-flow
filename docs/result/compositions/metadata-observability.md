---
title: Metadata and Observability
---

# Metadata and Observability

_Reading time: ~5 minutes. Prerequisite: [Getting Started](/getting-started)._ 

## Overview

This page covers metadata flow and observability composition:

```text
mergeMeta/mapMeta -> tapMeta/inspect* -> toDebugArray
```

Use it when you need durable correlation fields and safe debug output.

## Default behavior

- Metadata exists on both success and failure branches.
- `mergeMeta` adds keys without replacing the full map.
- `mapMeta` replaces metadata with your returned array.
- `tapMeta`, `tap`, `inspect`, and `inspectError` are side-effect hooks and do not change branch/payload.
- `toDebugArray` applies debug-safe sanitization.

## When to use

- Add request/trace IDs and operation context early.
- Track retry attempts, release failures, and decision points.
- Produce log-safe snapshots at boundaries.

## When not to use

- Do not store secrets in metadata unless sanitization is guaranteed.
- Avoid replacing metadata wholesale unless you intentionally drop previous keys.
- Avoid mutating metadata in ways that break downstream assumptions.

## Composes with

- [`mergeMeta`](/api#mergemeta-array-meta-result)
- [`mapMeta`](/api#mapmeta-callable-map-result)
- [`tapMeta`](/api#tapmeta-callable-tap-result)
- [`inspect` / `inspectError`](/api#tap-and-inspection-methods)
- [`toDebugArray`](/api#todebugarray-callable-sanitizer-null-array)

## Example progression

### Minimal snippet

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$dto = ['order_id' => 10];

$result = Result::ok($dto, ['request_id' => 'r-1'])
    ->mergeMeta(['operation' => 'orders.create'])
    ->tapMeta(fn (array $meta) => logger()->info('result-meta', $meta));
```

### Production-shaped snippet

```php
<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

$result = Result::retryDefer(
    times: 3,
    fn: fn () => $client->send($payload),
    delay: 100,
    exponential: true,
)
    ->mergeMeta([
        'request_id' => $requestId,
        'operation' => 'gateway.send',
    ])
    ->inspectError(fn ($error, array $meta) => logger()->warning('gateway-failure', [
        'error' => (string) $error,
        'meta' => $meta,
    ]));

$debug = $result->toDebugArray();
```

Try it:
- `php examples\debug\debug-sanitization-demo.php`
- `php examples\retry\retry-defer-test.php`

## Failure modes and edge cases

- Overwriting keys with `mergeMeta` is last-write-wins.
- Large metadata payloads increase log noise; normalize to stable keys.
- `toArray` is not sanitized; use `toDebugArray` for diagnostics.

## Related API entries

- [Metadata operations](/api#metadata-operations)
- [Tap and inspection methods](/api#tap-and-inspection-methods)
- [State and value access](/api#state-and-value-access)
