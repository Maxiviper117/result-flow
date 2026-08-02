# Generator composition

Use `Result::flow()` for synchronous workflows with several dependent Result steps.

See the package `examples/flow/` directory for runnable demonstrations.
Use `examples/flow/try-flow-and-bind.php` to compare typed binding with exception capture.

```php
use Generator;
use Maxiviper117\ResultFlow\Result;

$result = Result::flow(function (): Generator {
    $user = yield findUser($id);
    $account = yield findAccount($user->accountId);

    return createInvoice($user, $account);
});
```

Rules:

- yield only `Result` values
- use each successful value from the next generator assignment
- expect the first failure to stop later steps
- allow plain final values or final returned `Result` values
- allow unexpected exceptions to escape
- preserve metadata and expect later keys to overwrite earlier keys
- use namespaced metadata keys for step-specific values
- use `Result::bracket()` for critical resource cleanup

Use fluent `then()` or `flatMap()` methods for short chains and simple transformations.

Keep the workflow result and complete it at the application boundary.

Do not use `flow()` for plain yielded values, callables, promises, futures, parallel work, or cancellation.

Use `Result::retry(...)` or `Result::retryDefer(...)` for retryable steps before you yield them.

End the returned `Result` at the application boundary with `match(...)`, `toResponse()`, `unwrap*()`, or `throwIfFail()`.
