---
title: Sanitization & Data Safety
---

# Sanitization & Data Safety

Redacting sensitive information before it hits your logs or external services is a critical part of modern application development. Result Flow provides built-in tools to help you do this automatically.

## Why sanitize?

When you call `toDebugArray()`, Result Flow doesn't just return a raw dump of your success values, errors, and metadata. Instead, it passes through a **sanitizer** to ensure that:

1.  **Secrets stay secret**: Passwords, API keys, and tokens are replaced with a redaction string.
2.  **Logs stay concise**: Massive strings (like raw XML/JSON payloads or Base64 images) are truncated to prevent "log bloat."
3.  **Context stays intact**: You still see the types and structure of your data without exposing the actual values.

## Default Redaction

By default, any key in an array that matches a sensitive pattern will have its value replaced with `***REDACTED***`.

### Default Sensitive Keys
The core library includes these defaults (case-insensitive):
- `password`, `pass`
- `secret`, `token`
- `api_key`, `apikey`
- `ssn`, `card`
- `authorization`

### Glob Pattern Matching

You can use flexible glob patterns for more precise control over what gets redacted.

- **`*` (Any sequence)**: `*token` matches `access_token` and `refresh_token`.
- **`?` (Single character)**: `?id` matches `uid` or `xid`, but not `userid`.
- **Implicit Substrings**: Any plain word without wildcards is treated as a substring match (e.g., `pass` matches `password`).

```php
// In config/result-flow.php
'sensitive_keys' => [
    'api_*',      // matches api_key, api_secret
    '*_token',    // matches access_token, user_token
    '?id',        // matches uid, xid
],
```

## String Truncation

Long strings can dominate log files and cause performance issues in log aggregators. By default, Result Flow:
- Truncates strings longer than **200 characters**.
- Appends a `…` suffix to indicated missing data.
- This is configurable via the `max_string_length` setting.

## When to use `toDebugArray()`

While `toArray()` gives you the raw state of the `Result`, you should prefer `toDebugArray()` in the following scenarios:

### 1. Centralized Logging
When logging result outcomes to ELK, CloudWatch, or Splunk.
```php
Log::info('Order processed', $result->toDebugArray());
```

### 2. Error Reporting
When attaching context to a Sentry or Bugsnag report.
```php
Sentry::addBreadcrumb(
    category: 'pipeline',
    message: 'Step failed',
    data: $result->toDebugArray(),
);
```

### 3. Debugging Production
When you need to see why a specific user is failing without actually seeing their PII (Personally Identifiable Information).

## Custom Sanitization

If the default rules aren't enough, you can provide your own closure to `toDebugArray()`:

```php
$debug = $result->toDebugArray(function (mixed $value) {
    if ($value instanceof User) {
        return ['user_id' => $value->id]; // Only log the ID, never the object
    }
    return $value;
});
```

The closure is applied recursively to the metadata and error payloads.
