# Resource cleanup

Use the resource helper to release an acquired resource even if use fails.

```php
use Maxiviper117\ResultFlow\ResourceScope;

$result = ResourceScope::run(
    acquire: fn () => fopen('php://temp', 'w+'),
    use: fn ($stream) => fwrite($stream, 'hello'),
    release: fn ($stream) => fclose($stream),
);
// Ok(number of bytes written), or Fail if acquisition/use/release throws.
```

The helper captures thrown exceptions. A failed acquisition skips release; after acquisition, release is called once. If use and release fail, use remains the failure and the release exception is in `bracket.release_exception` metadata. Related: [boundary reference](/reference/boundaries) and [transaction guide](/guides/transaction-rollback).
