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

`Output\DebugOutput::toArray($result, ?callable $sanitizer = null)` returns types, selected error details, and sanitized metadata. Defaults redact sensitive keys and truncate long strings. Laravel configuration lives under `result-flow.debug`: `enabled`, `redaction`, `sensitive_keys`, `max_string_length`, `truncate_strings`, `sanitize_objects`, `object_max_depth`, and `array_max_depth`. Arrays are bounded independently (default 32, clamped to 1–128) and truncated with `[array_depth_exceeded]`; recursive array references are replaced with `[circular_reference]`. Disabling sanitization or supplying a custom sanitizer bypasses this built-in bound. Object traversal is opt-in; review its configuration before logging. Structured causes are expanded before sanitization with a maximum depth of 32 and a shared budget of 256 cause nodes; omitted branches are marked `[cause_limit_exceeded]`. This limit also applies to custom or disabled sanitizers. Diagnostic output is not a public response contract.

For a `ResultError` with a `Cause`, `error_cause` contains the nested cause data after the configured sanitizer runs, including redaction of matching metadata keys. It is `null` when no structured cause is present. Custom or disabled sanitization may expose sensitive data.

Sensitive key matching is case-insensitive. Plain patterns match substrings, and wildcards can span line breaks in keys. The string limit counts UTF-8 code points for valid UTF-8 text and preserves complete characters even without `mbstring`.

### Inspect a sanitized field

```php
use Maxiviper117\ResultFlow\Output\DebugOutput;
use Maxiviper117\ResultFlow\Result;

$result = Result::fail('timeout', ['trace_id' => 'r7', 'api_token' => 'private']);
$debug = DebugOutput::toArray($result);
echo $debug['meta']['trace_id'];  // r7
echo $debug['meta']['api_token']; // ***REDACTED*** with default settings
```

Use the sanitized array for diagnosis. For an HTTP response, choose client-visible fields with [explicit boundary mapping](/recipes/api-boundary-mapping).

## Interpret truncation markers

| Returned text | What happened |
| --- | --- |
| `[circular_reference]` | The sanitizer encountered an array reference already on its traversal path |
| `[array_depth_exceeded]` | Nested input reached the configured traversal limit |
| Configured redaction string | The metadata key matched a sensitive-key rule |

These markers describe the diagnostic copy. The original Result still holds its input metadata.
