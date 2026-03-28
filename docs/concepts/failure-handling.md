---
title: Failure Handling
---

# Failure Handling

Failure handling is where a branch can stay failed, recover, or be finalized.

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::fail('timeout')
    ->mapError(fn (string $error) => ['code' => 'TIMEOUT', 'message' => $error]);
```

## The main tools

- `mapError(...)` changes the failure payload.
- `otherwise(...)` handles the failure branch and can either recover or keep failing.
- `catchException(...)` handles failure values that are `Throwable` instances by class.
- `matchError(...)` and `catchError(...)` handle structured domain errors that implement `ResultError`.
- `recover(...)` always returns success.
- `throwIfFail(...)` converts failure back into exception-style control flow.

## Structured domain errors

When failure is part of normal domain behavior, prefer named classes extending
`DataTaggedError` over ad-hoc strings or arrays.

```php
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\DataTaggedError;

final class UserPersistError extends DataTaggedError
{
    public const CODE = 'E_USER_PERSIST';
}

$result = Result::fail(UserPersistError::from('Unable to save user'));

$message = $result->matchError(
    [UserPersistError::class => fn (UserPersistError $e) => $e->message()],
    onSuccess: fn ($value) => 'ok',
    onUnhandled: fn ($error) => 'unhandled',
);
```

Use the class to distinguish one domain failure from another. The string `code()`
is still useful for JSON output, logs, and external contracts, but class identity
is the matching mechanism.

## How to think about it

Use `otherwise(...)` when the next step is still part of the Result flow.

Use `recover(...)` only when you intentionally want to stop carrying the failure branch forward.

Use `throwIfFail(...)` at a boundary that expects exceptions, such as a transaction closure.

Use `catchException(...)` for infrastructure/library exceptions.

Use `matchError(...)` / `catchError(...)` for class-based domain errors.

## Common mistakes

- Recovering too early and hiding useful error context.
- Mixing unrelated failure shapes in one chain.
- Using `throwIfFail(...)` deep inside the domain instead of at the edge.

## Related pages

- [Finalization boundaries](/concepts/finalization-boundaries)
- [Failure handling reference](/reference/failure-handling)
