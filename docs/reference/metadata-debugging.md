# Metadata and debugging

Metadata is diagnostic, shallow, and separate from active payloads. See [metadata concepts](/concepts/metadata).

## `meta(): array`

Returns the current string-keyed bag without invoking callbacks or changing the Result.

```php
$meta = Result::ok(1, ['trace_id' => 't-1'])->meta();
```

Related: [validate then persist](/guides/validate-then-persist).

## `withMeta(array $meta): Result`

Returns a new Result with the same tag and payload and the entire bag replaced. It does not invoke callbacks or catch exceptions. Nested objects remain shared.

```php
$cleared = Result::ok(1, ['trace_id' => 't-1'])->withMeta([]);
```

Related: [metadata concepts](/concepts/metadata).

## Debug output

`Output\DebugOutput::toArray($result, ?callable $sanitizer = null)` returns types, selected error details, and sanitized metadata. Defaults redact sensitive keys and truncate long strings. Laravel configuration lives under `result-flow.debug`: `enabled`, `redaction`, `sensitive_keys`, `max_string_length`, `truncate_strings`, `sanitize_objects`, and `object_max_depth`, and `array_max_depth`. Arrays are bounded independently (default 32, clamped to 1–128) and truncated with `[array_depth_exceeded]`; recursive array references are replaced with `[circular_reference]`. Disabling sanitization or supplying a custom sanitizer bypasses this built-in bound. Object traversal is opt-in; review its configuration before logging. Diagnostic output is not a public response contract.

For a `ResultError` with a `Cause`, `error_cause` contains the nested cause data after the configured sanitizer runs, including redaction of matching metadata keys. It is `null` when no structured cause is present. Custom or disabled sanitization may expose sensitive data.
