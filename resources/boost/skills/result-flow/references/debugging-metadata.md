# Debugging and Metadata Reference

Use when diagnosing failures and preserving safe observability.

## Decision table

| Need | Method |
|---|---|
| Add metadata keys | `mergeMeta` |
| Replace metadata map | `mapMeta` |
| Non-invasive metadata inspection | `tapMeta` |
| Branch inspection | `inspect` / `inspectError` |
| Safe diagnostics output | `toDebugArray` |

## Guidance

- Keep metadata keys stable (`request_id`, `operation`, `trace_id`).
- Prefer `toDebugArray` in logs/diagnostics.
- Keep instrumentation side-effect only.

## Anti-patterns

- Logging raw `toArray()` with sensitive fields.
- Mutating metadata contracts across chain steps.

## Example shape

```php
$result = $result
    ->mergeMeta(['operation' => 'checkout'])
    ->inspectError(fn ($e, $meta) => logger()->warning('failed', compact('e', 'meta')));
```
