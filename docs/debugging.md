---
title: Debugging and Meta
---

# Debugging and Meta

Use this page for practical debugging strategy.

## Safe debug output

Use `toDebugArray()` for logs and monitoring payloads that might contain sensitive values.

```php
$debug = $result->toDebugArray();
```

## Raw output

Use `toArray()` only for trusted internal output where redaction is not required.

## Tap hooks for observability

- `tapMeta` to inspect metadata
- `onSuccess` / `onFailure` for branch-specific logs
- `tap` for unified event emission

## Failure diagnostics checklist

1. Confirm branch: `isFail()`.
2. Inspect `error()` type and message.
3. Inspect relevant metadata keys (`request_id`, `step`, `failed_value`).
4. Emit `toDebugArray()` to logs.

## Related pages

- [Metadata and Debugging](/result/metadata-debugging)
- [Sanitization Guide](/sanitization)
