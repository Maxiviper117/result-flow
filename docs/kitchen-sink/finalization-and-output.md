---
title: Finalization and Output
---

# Finalization and Output

This group covers the functions that close a flow or turn it into transport-safe output.

## Quick Map

| Function         | What it does                                             |
| ---------------- | -------------------------------------------------------- |
| `match`          | Finishes the result by handling both branches explicitly |
| `matchException` | Handles Throwable failures by class                      |
| `unwrap`         | Returns the success value or throws the failure          |
| `unwrapOr`       | Returns the success value or an eager default            |
| `unwrapOrElse`   | Returns the success value or a lazy default              |
| `getOrThrow`     | Returns the success value or throws a custom exception   |
| `throwIfFail`    | Throws on failure and returns the same result on success |
| `toArray`        | Returns the raw branch shape                             |
| `toDebugArray`   | Returns debug-safe output                                |
| `toJson`         | Serializes to JSON                                       |
| `toXml`          | Serializes to XML with name normalization                |
| `toResponse`     | Converts to an HTTP response shape                       |

## match

`match(...)` finishes the result by handling both branches explicitly.

```php
match(callable $onSuccess, callable $onFailure): mixed
```

### Inputs:

* `$onSuccess`: callback invoked with the success value and metadata
* `$onFailure`: callback invoked with the failure value and metadata

Use it when the caller needs a branch-aware output value.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$payload = Result::ok(['id' => 42], ['source' => 'cache'])
    ->match(
        onSuccess: fn (array $value, array $meta) => [
            'ok' => true,
            'data' => $value,
            'source' => $meta['source'],
        ],
        onFailure: fn ($error, array $meta) => [
            'ok' => false,
            'error' => $error,
            'source' => $meta['source'] ?? 'unknown',
        ],
    );

var_dump($payload);
```

## matchException

`matchException(...)` handles Throwable failures by class.

```php
matchException(array $exceptionHandlers, callable $onSuccess, callable $onUnhandled): mixed
```

### Inputs:

* `$exceptionHandlers`: map of Throwable class to callback for matching exceptions
* `$onSuccess`: callback invoked for successful results
* `$onUnhandled`: callback invoked for failures that do not match any handler

Use it when exception class determines the final output shape.

Use:

```php
use Maxiviper117\ResultFlow\Result;

function validateEmail(string $email): Result {
    if (empty($email)) {
        return Result::fail(new InvalidArgumentException('Email is required'));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return Result::fail(new InvalidArgumentException('Invalid email format'));
    }

    return Result::ok($email);
}

$result = validateEmail('user@example.com')->matchException(
    exceptionHandlers: [
        InvalidArgumentException::class => fn ($error, array $meta) => 'Validation error: ' . $error->getMessage(),
    ],
    onSuccess: fn ($email, array $meta) => "Email valid: {$email}",
    onUnhandled: fn ($error, array $meta) => "Unexpected error: {$error}",
);

echo $result;
```

## unwrap

`unwrap()` extracts the value from a successful Result, but **throws an exception if the Result is a failure**. Use it only when failure is unexpected or at application boundaries.

```php
unwrap(): mixed
```

### Behavior:

- if the failure value is a `\Throwable`, `unwrap()` re-throws it as-is
- otherwise `unwrap()` wraps the failure in a `RuntimeException` with the string-cast failure value as the message

It is the strictest boundary helper, so use it only when the caller expects plain values and failure should escape immediately.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$success = Result::ok(['id' => 1, 'name' => 'John']);
$value = $success->unwrap();
// ['id' => 1, 'name' => 'John']

$failure = Result::fail('User not found');
$value = $failure->unwrap();
// ❌ Throws: RuntimeException("User not found")
```

### Where to use it:

```php
// ✅ At application boundary (error handling layer)
class UserController {
    public function show(int $id): JsonResponse {
        try {
            $user = $this->userService->findUser($id)->unwrap();
            return response()->json(['ok' => true, 'data' => $user]);
        } catch (RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }
}

// ✅ When failure is truly exceptional
$result = Result::of(fn () => json_decode($json, true, 512, JSON_THROW_ON_ERROR));
$data = $result->unwrap();  // If JSON fails, something is very wrong
```

## unwrapOr

`unwrapOr()` returns the value on success, or a pre-computed fallback on failure (eager evaluation). Use it when you have a cheap default value available and do not need failure context to compute it.

```php
unwrapOr(mixed $default): mixed
```

### Inputs:

* `$default`: eager fallback returned when the result is a failure

### Key Point: It's EAGER

- the default value is evaluated immediately, whether the Result succeeds or fails

The fallback is evaluated before the call, so use it only when that default is cheap or already available.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['id' => 1]);
$value = $result->unwrapOr(['id' => 0]); // ['id' => 1]

$result = Result::fail('User not found');
$value = $result->unwrapOr(['id' => 0]); // ['id' => 0]

$value = $failure->unwrapOr(['id' => 0, 'name' => 'Unknown']);
// ['id' => 0, 'name' => 'Unknown']

// Fallback is evaluated IMMEDIATELY, even if not needed
$expensive = heavyComputation();  // ← Always runs!
$value = $success->unwrapOr($expensive);  // Computed for nothing
```

## unwrapOrElse

`unwrapOrElse()` returns the success value or computes a lazy fallback. If you need to inspect the error or compute the default dynamically, use `unwrapOrElse()`.

```php
unwrapOrElse(callable $fn): mixed
```

### Inputs:

* `$fn`: callback invoked with the failure value and metadata to compute the fallback

### Key Point: It's LAZY

- the fallback is computed by a callback that receives the failure value and metadata
- the callback is only invoked if the result is a failure

The callback receives the failure value and metadata, so you can derive a fallback from the failure context.

Use:

```php
use Maxiviper117\ResultFlow\Result;

function getUserData(int $userId): Result {
    return Result::of(fn () => $database->find($userId))
        ->mapError(fn ($e) => "Database error: {$e->getMessage()}");
}

// At boundary, with fallback
$userData = getUserData(123)
    ->unwrapOrElse(fn ($error, $meta) => [
        'id' => null,
        'name' => 'Guest',
        'status' => 'error',
        'message' => $error
    ]);

return response()->json($userData);
```

## getOrThrow

`getOrThrow()` extracts the value from a Result, but throws a custom exception of your choice if it fails.

```php
getOrThrow(callable $exceptionFactory): mixed
```

### Inputs:

* `$exceptionFactory`: callback invoked with the failure value and metadata to build the thrown exception

Use it when you want a domain-specific exception type at the boundary instead of the raw failure value.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['id' => 1, 'name' => 'John']);
$user = $result->getOrThrow(fn ($error) => new Exception('Failed'));

$result = Result::fail('User not found', ['entity' => 'user']);
$user = $result->getOrThrow(
    fn ($error, $meta) => new NotFoundException("Entity {$meta['entity']} not found: {$error}")
);
```

## throwIfFail

`throwIfFail()` throws an exception if the Result is in a failure state, otherwise returns the Result unchanged.

```php
throwIfFail(): self
```

### Key Point: Chainable vs Extract

- `unwrap()` extracts the value and throws on failure
- `throwIfFail()` keeps the Result, throws on failure, and allows chaining

That makes it useful when you want an exception boundary without losing the fluent chain on success.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['id' => 1])->throwIfFail();

$result = Result::fail('timeout');
$result->throwIfFail(); // Throws RuntimeException('timeout')
```

```php
$result = Result::ok($data)
    ->then(new ValidateAction())
    ->throwIfFail()
    ->then(new PersistAction())
    ->then(new NotifyAction());
```

| Method           | Success       | Failure            | Chainable | Use Case                     |
| ---------------- | ------------- | ------------------ | --------- | ---------------------------- |
| `unwrap()`       | Returns value | ❌ Throws           | No        | Boundary layer               |
| `unwrapOr()`     | Returns value | Uses eager default | No        | Quick fallback               |
| `unwrapOrElse()` | Returns value | Calls callback     | No        | Lazy fallback + error access |
| `getOrThrow()`   | Returns value | Custom exception   | No        | Custom exceptions            |
| `throwIfFail()`  | Returns self  | Throws             | ✅ Yes     | Chain + fail-fast            |

These functions convert a Result into a plain value or a thrown exception.

- `unwrap()` returns the value or throws the failure
- `unwrapOr()` returns the value or an eager default
- `unwrapOrElse()` returns the value or computes a lazy default from error and metadata
- `getOrThrow()` returns the value or throws a custom exception
- `throwIfFail()` throws on failure and otherwise returns the same result so chaining can continue

Use them only when the boundary genuinely expects plain values or exceptions.

## toArray

`toArray()` returns the raw branch shape.

```php
toArray(): array
```

```php
['ok' => bool, 'value' => mixed, 'error' => mixed, 'meta' => array]
```

Use it for trusted internal serialization or inspection when redaction is not needed.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$payload = Result::ok(['id' => 42], ['request_id' => 'r-1'])->toArray();

var_dump($payload);
```

## toDebugArray

`toDebugArray(...)` converts a Result into a debug-safe array representation, useful for logging and monitoring. It can sanitize/redact sensitive data via a custom callback

```php
toDebugArray(?callable $sanitizer = null): array
```

### Inputs:

* `$sanitizer`: optional sanitizer callback for values in the debug payload

- success includes `value_type`
- failure includes `error_type` and `error_message`
- metadata is sanitized
- sensitive keys are redacted and long strings are truncated by default

Use it for logs, traces, and diagnostics.

Use:

```php
use Maxiviper117\ResultFlow\Result;

// Success Result
$success = Result::ok(['id' => 1, 'email' => 'user@example.com']);
print_r($success->toDebugArray());

// Output:
// [
//     'state' => 'ok',
//     'value' => ['id' => 1, 'email' => 'user@example.com'],
//     'metadata' => [],
//     'error' => null,
// ]

// Failure Result
$failure = Result::fail('Validation failed');
print_r($failure->toDebugArray());

// Output:
// [
//     'state' => 'fail',
//     'value' => null,
//     'metadata' => [],
//     'error' => 'Validation failed',
// ]
```

### With Metadata:

```php
$result = Result::fail('Auth failed', ['token' => 'secret-token-xyz', 'attempt' => 3]);
print_r($result->toDebugArray());

// Output:
// [
//     'state' => 'fail',
//     'value' => null,
//     'metadata' => [
//         'token' => 'secret-token-xyz',  // ⚠️ Exposed!
//         'attempt' => 3,
//     ],
//     'error' => 'Auth failed',
// ]
```

### toDebugArray() with Sanitizer Callback
Redact sensitive values before logging:

```php
$result = Result::fail('Auth failed', [
    'token' => 'secret-token-xyz',
    'user_id' => 42,
    'email' => 'user@example.com'
]);

// Sanitize function
$sanitizer = function (mixed $value): mixed {
    if (is_string($value) && strlen($value) > 20) {
        return substr($value, 0, 20) . '...';
    }
    return $value;
};

print_r($result->toDebugArray($sanitizer));

// Output:
// [
//     'state' => 'fail',
//     'value' => null,
//     'metadata' => [
//         'token' => 'secret-token-xyz...',  // ✅ Redacted
//         'user_id' => 42,
//         'email' => 'user@example.com',
//     ],
//     'error' => 'Auth failed',
// ]
```

### Real-World: Logging with Sanitization

```php
use Maxiviper117\ResultFlow\Result;

class AuthService {
    public function login(string $email, string $password): Result {
        $result = Result::of(fn () => $this->authenticateUser($email, $password))
            ->mapError(fn ($e) => "Login failed: {$e->getMessage()}");
        
        // Log with sensitive data redacted
        $debug = $result->toDebugArray(function (mixed $value): mixed {
            // Redact passwords
            if (is_string($value) && stripos($value, 'password') !== false) {
                return '***REDACTED***';
            }
            
            // Truncate long tokens
            if (is_string($value) && strlen($value) > 50) {
                return substr($value, 0, 50) . '...';
            }
            
            return $value;
        });
        
        Log::info('Login attempt', $debug);  // ✅ Safe to log
        
        return $result;
    }
}
```

## toJson

`toJson(...)` serializes the raw branch shape to JSON.

```php
toJson(int $options = 0): string
```

### Inputs:

* `$options`: JSON encoding options merged with `JSON_THROW_ON_ERROR`

- uses `JSON_THROW_ON_ERROR`
- accepts JSON encoding options

Use it when the boundary expects JSON text.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$json = Result::ok(['id' => 42])->toJson(JSON_PRETTY_PRINT);

echo $json;
```

## toXml

`toXml(...)` serializes the raw branch shape to XML.

```php
toXml(string $rootElement = 'result'): string
```

### Inputs:

* `$rootElement`: root node name used for the generated XML document

- invalid characters become underscores
- names that cannot start as XML elements are prefixed with `item_`
- names that begin with `xml` are prefixed with `item_`
- numeric keys become `item{n}`

Use it when the boundary expects XML, but do not treat it as a lossless key mirror.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$xml = Result::ok(['order-id' => 42])->toXml('checkout_result');

echo $xml;
```

## toResponse

`toResponse(...)` converts the result to an HTTP response.

```php
toResponse(): mixed
```

- in Laravel, it returns a JSON response object when the response factory exists
- outside Laravel, it returns an array with `status`, `headers`, and a JSON string `body`
- status is `200` for success and `400` for failure

Use it at HTTP boundaries, not deep in domain code.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$response = Result::fail('invalid state', ['step' => 'checkout'])->toResponse();

var_dump($response);
```

## See Also

- [Boundary reference](/reference/boundaries)
- [Kitchen sink overview](./)
