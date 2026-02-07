---
title: Laravel Metadata and Taps
---

# Laravel Metadata and Taps

## Scenario

Attach request context and emit branch-specific telemetry.

## Example

```php
$result = Result::ok($payload, ['request_id' => (string) Str::uuid()])
    ->onSuccess(fn ($value, array $meta) => logger()->info('pipeline-ok', $meta))
    ->onFailure(fn ($error, array $meta) => logger()->warning('pipeline-fail', ['error' => $error, 'meta' => $meta]))
    ->mergeMeta(['controller' => 'CheckoutController@store']);
```

## Expected behavior

- Tap callbacks do not mutate payload.
- Metadata stays available at every stage.

## Related pages

- [Metadata and Debugging](/result/metadata-debugging)
- [Debugging and Meta](/debugging)
