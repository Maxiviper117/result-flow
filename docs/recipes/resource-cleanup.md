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

| Where failure occurs | What the caller gets | Does release run? |
| --- | --- | --- |
| Acquire throws or returns Fail | Acquisition failure | No |
| Use throws or returns Fail | Use failure | Once |
| Release throws after successful use | Release exception as the failure | Once |
| Release throws after failed use | Use failure plus the release exception in metadata | Once |

A PHP function that signals failure by returning `false` needs an explicit check. For example, wrap a failed `fopen` in `Result::fail('Could not open stream')` before passing control to the use callback. A bare boolean is an ordinary value to the helper.

When you type the use and release parameters, use the resource itself. If acquire returns `Result<Connection, ConnectionError>`, both callbacks accept `Connection`. A use callback returning `int` produces `Result<int, ConnectionError|Throwable>`, which accounts for the original acquisition failure and captured exceptions.

## Run the stream example

From the checkout, run `php examples/v2/resource.php`. The temporary stream is opened, receives the five ASCII bytes in `hello`, and is closed before the script prints `Wrote 5 bytes`. To exercise the failure path, throw a `RuntimeException` in the use callback. Release still runs and the script prints `Failed`.
