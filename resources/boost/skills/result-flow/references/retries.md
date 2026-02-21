# Retries Reference

Use when operations can fail transiently and need bounded retry policies.

## Decision table

| Need | Method |
|---|---|
| Simple retry policy | `retry` |
| Attempt callback may return value/`Result` or throw | `retryDefer` |
| Advanced predicates/hooks/backoff | `retrier` |

## Guidance

- Retry only transient failure classes.
- Keep retry budgets explicit and bounded.
- After retries complete, map terminal failures intentionally.

## Anti-patterns

- Retrying validation or deterministic business-rule failures.
- Unbounded delays/attempt counts without observability.

## Example shape

```php
$result = Result::retryDefer(3, fn () => send($payload), delay: 100, exponential: true)
    ->otherwise(fn ($error) => mapTerminalFailure($error));
```
