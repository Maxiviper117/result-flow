---
title: Construction and Entry Points
---

# Construction and Entry Points

This group covers the functions that create a result, retry work, or guard a resource lifecycle.

## Quick Map

| Function        | What it does                                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------------------------------ |
| `ok`            | Creates a success result                                                                                           |
| `fail`          | Creates a failure result                                                                                           |
| `failTagged`    | Creates a failure result containing a structured `DataTaggedError`                                                 |
| `failWithValue` | Creates a failure result and stores the rejected input in metadata                                                 |
| `of`            | Runs a callback that returns a plain value or throws; returned `Result` values are wrapped as plain success values |
| `defer`         | Runs a callback that may return a value, a `Result`, or throw; returned `Result` values are preserved              |
| `retry`         | Runs a callback with a simple retry policy                                                                         |
| `retryDefer`    | Retries a `defer`-style callback                                                                                   |
| `retrier`       | Returns the fluent retry builder                                                                                   |
| `bracket`       | Runs acquire/use/release with cleanup guarantees                                                                   |

## ok

`ok(...)` creates a success branch with the provided value and metadata.

```php
ok(mixed $value, array $meta = []): self
```

### Inputs:

* `$value`: success value to store in the `Ok` branch
* `$meta`: metadata to attach to the result

Use it when you already know the branch is successful and want the chain to continue from that success state.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::ok(['id' => 1], ['request_id' => 'r-1']);
```

## fail

`fail(...)` creates a failure branch with the provided error payload and metadata.

```php
fail(mixed $error, array $meta = []): self
```

### Inputs:

* `$error`: failure payload to store in the `Fail` branch
* `$meta`: metadata to attach to the result

Use it when failure is already known and should remain explicit instead of being thrown.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::fail('Invalid state', ['step' => 'validate']);
```

## failWithValue

`failWithValue(...)` creates a failure and stores the rejected value in `meta['failed_value']`.

```php
failWithValue(mixed $error, mixed $failedValue, array $meta = []): self
```

## failTagged

`failTagged(...)` creates a failure with a structured `DataTaggedError`.

```php
failTagged(string $code, string $message, mixed $payload = null, array $meta = [], ?Cause $cause = null): self
```

Use it for quick structured failures. For named domain errors, prefer a subclass of
`DataTaggedError` with a `CODE` constant and construct it via `::from(...)`.

### Inputs:

* `$error`: failure payload to store in the `Fail` branch
* `$failedValue`: rejected value to record in metadata
* `$meta`: metadata to attach to the result

Use it when the caller may need the input that caused the failure, such as validation or transform pipelines.

Use:

```php
use Maxiviper117\ResultFlow\Result;

// Standard fail - loses the original value
$result1 = Result::fail('Invalid email');
// Metadata: none

// failWithValue - preserves what failed
$result2 = Result::failWithValue('Invalid email', 'bad-email@invalid');
// Metadata: ['failed_value' => 'bad-email@invalid']

// With additional metadata
$result3 = Result::failWithValue(
    'Invalid email',
    'user@bad',
    ['field' => 'email', 'step' => 'validation']
);
```

## of

`of(...)` wraps a callable (function) that might throw an exception, automatically catching any thrown exceptions and converting them into a failed Result.

```php
of(callable $fn): self
```

### Inputs:

* `$fn`: callback that should return a plain success value

### Behavior:

- **Exception boundary** - Catches all exceptions
- **Automatic conversion** - Thrown exceptions become failures
- **Always wraps success** - Success returns the function result and wraps it in `ok()`
- **Does not flatten returned `Result` values** - if the callback returns `Result::fail(...)`, the outer result is still `ok(...)`
- **Safe error handling** - No try/catch boilerplate needed

Use it when the callback always returns a plain value on success and you want exceptions converted into a failure branch.

Use `of(...)` when the callback contract is:

- success => plain value
- failure => thrown exception

Do **not** use it when the callback may already return `Result`, because `of(...)` treats that returned `Result` as plain data.

Use:

```php
use Maxiviper117\ResultFlow\Result;

// Function that throws
function riskyJsonDecode(string $json): array {
    return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
}

// Wrap with Result::of
$result = Result::of(fn () => riskyJsonDecode('{"key": "value"}'));
// Result: ok(['key' => 'value'])

// On error, exception is caught
$result = Result::of(fn () => riskyJsonDecode('invalid json'));
// Result: fail(JsonException(...))
```

```php
// Before (try/catch boilerplate)
try {
    $user = fetchUser(123);
    // ... process
} catch (Exception $e) {
    // ... handle
}

// After (clean Result flow)
Result::of(fn () => fetchUser(123))
    ->then(new ProcessUserAction())
    ->otherwise(fn ($e) => handleError($e));
```

### Important distinction from `defer`

```php
use Maxiviper117\ResultFlow\Result;

$wrapped = Result::of(fn () => Result::fail('upstream failed'));

$wrapped->isOk();   // true
$wrapped->value();  // Result::fail('upstream failed')
```

That behavior is intentional: `of(...)` always wraps the callback's return value as the success payload unless the callback throws.

## defer

`defer(...)` executes a callback and normalizes its output - plain values become ok(), Result objects pass through as-is, and thrown exceptions become fail().

```php
defer(callable $fn): self
```

### Inputs:

* `$fn`: callback that may return a value, return a `Result`, or throw

### Behavior:

- plain value becomes success
- returned `Result` is returned as-is
- thrown exception becomes failure

Use it when the callback already mixes values and `Result` returns.

Use `defer(...)` when the callback contract is:

- success may be a plain value
- failure may be a returned `Result::fail(...)`
- unexpected problems may still throw

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::defer(fn () => fetchUser($id));
```

### Important distinction from `of`

```php
use Maxiviper117\ResultFlow\Result;

$preserved = Result::defer(fn () => Result::fail('upstream failed'));

$preserved->isFail(); // true
$preserved->error();  // 'upstream failed'
```

Unlike `of(...)`, `defer(...)` preserves returned `Result` instances instead of wrapping them as success values.

## Choosing between `of` and `defer`

| If the callback...                                                         | Use          | Why                                                    |
| -------------------------------------------------------------------------- | ------------ | ------------------------------------------------------ |
| returns a plain value on success and throws on failure                     | `of(...)`    | keeps the contract narrow and explicit                 |
| may return a plain value, `Result::ok(...)`, `Result::fail(...)`, or throw | `defer(...)` | normalizes all paths into one `Result` without nesting |

Quick mental model:

- `of(...)` = catch exceptions and wrap the return value
- `defer(...)` = catch exceptions and preserve any returned `Result`

## retry

`retry(...)` executes a callable multiple times until it succeeds, with support for fixed delays or exponential backoff between attempts.

```php
retry(int $times, callable $fn, int $delay = 0, bool $exponential = false): Result
```

### Inputs:

* `$times`: maximum number of attempts
* `$fn`: callback to run on each attempt
* `$delay`: base delay between attempts in milliseconds
* `$exponential`: whether to use exponential backoff

### Behavior:

- the callback runs until success or retry exhaustion
- plain values become success
- thrown exceptions become failure

Use it when the callback already fits the retry contract and you only need attempt count, delay, and optional exponential backoff.

Use:

```php
use Maxiviper117\ResultFlow\Result;

// Retry API call 3 times with 100ms delay
$result = Result::retry(
    times: 3,
    fn: fn () => $httpClient->get('https://api.example.com/users'),
    delay: 100,
    exponential: false  // Fixed delay
);

$result->match(
    onSuccess: fn ($users) => print("✅ Got users: " . count($users)),
    onFail: fn ($error) => print("❌ All retries failed: {$error}")
);
```

```php
// Retries with increasing delays: 100ms, 200ms, 400ms
$result = Result::retry(
    times: 3,
    fn: fn () => $database->connect(),
    delay: 100,        // Base delay
    exponential: true  // Doubles each attempt
);

// Attempt 1: fails, waits 100ms
// Attempt 2: fails, waits 200ms
// Attempt 3: fails, waits 400ms
// Result: fail
```

## retryDefer

`retryDefer(...)` retries a callback that might return a plain value, a Result object, or throw an exception. It normalizes all three cases and automatically retries on failures.

```php
Result::retryDefer(
    times: 3,              // Number of attempts
    fn: fn () => callback(),
    delay: 100,            // Milliseconds between attempts
    exponential: true      // Optional exponential backoff
): self
```

### Inputs:

* `$times`: maximum number of attempts
* `$fn`: callback that may return a value, return a `Result`, or throw
* `$delay`: base delay between attempts in milliseconds
* `$exponential`: whether to use exponential backoff

### Behavior:

- each attempt may return a value, return a `Result`, or throw
- the attempt result is normalized first
- the retry policy decides whether to continue

Use it when you want a retry loop around mixed callback behavior.

Use:

```php
use Maxiviper117\ResultFlow\Result;
use RuntimeException;

$attempt = 0;
$send = function () use (&$attempt): array {
    $attempt++;
    if ($attempt < 3) {
        throw new RuntimeException("Attempt {$attempt} failed");
    }
    
    // Success on 3rd attempt
    return ['ok' => true, 'attempt' => $attempt];
};

$result = Result::retryDefer(
    times: 3,
    fn: $send,
    delay: 100,
    exponential: true
);

// Attempt 1: throws → wait 100ms
// Attempt 2: throws → wait 200ms (exponential)
// Attempt 3: returns ['ok' => true, 'attempt' => 3] → ✅ SUCCESS

$result->match(
    onSuccess: fn ($data) => print("✅ " . json_encode($data)),
    onFail: fn ($error) => print("❌ {$error}")
);

// Output: ✅ {"ok":true,"attempt":3}
```

### Handle Terminal Failure

```php
$result = Result::retryDefer(
    times: 3,
    fn: fn () => throw new RuntimeException("Always fails"),
    delay: 50,
    exponential: true
)->otherwise(fn ($error) => Result::fail("Retry exhausted: {$error}"));

$result->match(
    onFail: fn ($error) => print("❌ {$error}")
);

// Output: ❌ Retry exhausted: Always fails
```

### API Call with Mixed Returns:

```php
$attempt = 0;

$result = Result::retryDefer(
    times: 5,
    fn: function () use (&$attempt) {
        $attempt++;
        $response = $httpClient->get('https://unreliable-api.com/users');
        
        if ($response->status() === 200) {
            return $response->json();  // Plain array → wrapped in ok()
        }
        
        if ($response->status() === 429) {
            throw new Exception("Rate limited");  // Caught → retry
        }
        
        return Result::fail("Server error");  // Result → passed through
    },
    delay: 200,
    exponential: true
)->otherwise(fn ($error) => [
    'ok' => false,
    'error' => $error->getMessage(),
    'attempts' => $attempt
]);

$result->match(
    onSuccess: fn ($data) => ['ok' => true, 'data' => $data],
    onFail: fn ($error) => $error
);
```

### Use retryDefer() when your callback might:

- Return a plain value (gets wrapped in ok())
- Return a Result object (passed through as-is)
- Throw an exception (caught and retried)

Perfect for unreliable APIs, database connections, and flaky external services!

## retrier

`retrier()` is a fluent builder for advanced retry configurations. It gives you fine-grained control over retry policies with hooks, jitter, custom predicates, and more.

```php
Result::retrier()
    ->maxAttempts(5)
    ->delay(100)
    ->exponential(true)
    ->jitter(50)
    ->attempt(fn () => callback());
```

### Behavior:

- returns the fluent retry builder for more advanced retry configuration
- supports predicates, jitter, hooks, and attempt metadata
- Fluent API - Chain methods for clean configuration
- Jitter support - Add randomness to prevent thundering herd
- Custom predicates - Retry only on specific conditions
- Hooks - Execute code before/after attempts
- Complex retry policies - More control than simple retry()

Use it when you need retry predicates, jitter, hooks, or attempt metadata rather than the simple helper methods.

Use:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::retrier()
    ->maxAttempts(3)
    ->delay(100)
    ->exponential(true)
    ->attempt(fn () => $httpClient->get('https://api.example.com/data'));

$result->match(
    onSuccess: fn ($data) => print("✅ " . json_encode($data)),
    onFail: fn ($error) => print("❌ {$error}")
);
```

### With Jitter (Prevent Thundering Herd):

```php
// Without jitter: all retries happen at exact same intervals
// With jitter: adds randomness to spread load

$result = Result::retrier()
    ->maxAttempts(5)
    ->delay(100)
    ->exponential(true)
    ->jitter(50)  // ← Add 0-50ms random delay
    ->attempt(fn () => $database->connect());

// Attempt 1: fails, wait 100ms ± 50ms
// Attempt 2: fails, wait 200ms ± 50ms
// Attempt 3: fails, wait 400ms ± 50ms
// etc.
```

### With Predicates (Conditional Retry):

```php
$result = Result::retrier()
    ->maxAttempts(3)
    ->delay(100)
    ->retryWhen(fn ($error, $attempt) => 
        // Only retry on specific exceptions
        $error instanceof TimeoutException || 
        $error instanceof RateLimitException
    )
    ->attempt(fn () => $api->call());
```

### With Hooks (Execute Code During Retries):

```php
$result = Result::retrier()
    ->maxAttempts(5)
    ->delay(100)
    ->exponential(true)
    ->beforeAttempt(fn ($attempt) => 
        print("🔄 Attempting {$attempt}...\n")
    )
    ->afterAttempt(fn ($attempt, $result) =>
        $result->match(
            onSuccess: fn ($data) => print("✅ Success\n"),
            onFail: fn ($error) => print("❌ Failed: {$error}\n")
        )
    )
    ->attempt(fn () => $httpClient->get($url));
```

### Real-World: Laravel Action with Retries

```php
$result = Result::retrier()
    ->maxAttempts(3)
    ->delay(100)
    ->exponential(true)
    ->jitter(25)
    ->beforeAttempt(fn ($attempt) =>
        Log::info("Executing action attempt {$attempt}")
    )
    ->retryWhen(fn ($error) =>
        $error instanceof ConnectionException ||
        $error instanceof TimeoutException
    )
    ->attempt(fn () => (new SyncExternalAction())->execute($dto));

$result->match(
    onSuccess: fn ($data) => ['ok' => true, 'result' => $data],
    onFail: fn ($error) => ['ok' => false, 'error' => $error->getMessage()]
);
```

## Compare: retry() vs retryDefer() vs retrier()

| Method         | Simplicity | Control                                     | Use Case                                                                                                           |
| -------------- | ---------- | ------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| `retry()`      | Simple     | Basic (attempts, delay, exponential)        | When your callback fits the retry contract and you only need basic retry features.                                 |
| `retryDefer()` | Moderate   | Normalizes mixed callback behavior          | When your callback may return plain values, `Result`, or throw, and you want automatic normalization with retries. |
| `retrier()`    | Advanced   | Full control with predicates, jitter, hooks | When you need complex retry policies, conditional retries, or want to execute code during retry attempts.          |

## bracket

`bracket(...)` safely manages resource lifecycles - it acquires a resource, uses it, and always releases it (even on failure). This prevents resource leaks like unclosed files or database connections.

```php
Result::bracket(
    acquire: fn () => getResource(),      // Acquire resource
    use: fn ($resource) => useIt($resource),  // Use resource
    release: fn ($resource) => cleanup($resource)  // Always cleanup
)
```

### Inputs:

* `$acquire`: callback that acquires the resource
* `$use`: callback that consumes the resource
* `$release`: callback that always runs when release is possible

### Behavior:

- if acquire fails, release is not called
- if use fails, release is still attempted
- if release throws after a use failure, the original failure remains and the release exception is stored in metadata
- if use succeeds and release throws, the result becomes failure

Use it for resources that must be cleaned up even when the use step fails.

Use:

### File Handling Example:

```php
use Maxiviper117\ResultFlow\Result;

$result = Result::bracket(
    acquire: fn () => fopen('/path/to/file.txt', 'r'),
    use: fn ($handle) => fread($handle, 1024),
    release: fn ($handle) => fclose($handle)  // ← ALWAYS called
);

$result->match(
    onSuccess: fn ($content) => print("✅ Read: {$content}"),
    onFail: fn ($error) => print("❌ {$error}")
);

// Even if fread() fails, fclose() still executes!
```

### Database Connection Example:
```php
use Maxiviper117\ResultFlow\Result;

$result = Result::bracket(
    acquire: fn () => new PDO($dsn, $user, $pass),
    use: fn (PDO $db) => 
        $db->query('SELECT * FROM users')->fetchAll(PDO::FETCH_ASSOC),
    release: fn (PDO $db) => 
        $db = null  // Connection properly closed
);

$result->match(
    onSuccess: fn ($users) => print("✅ Got " . count($users) . " users"),
    onFail: fn ($error) => print("❌ {$error}")
);
```

### Try/Catch Equivalent:

```php
// Traditional try/finally pattern
try {
    $handle = fopen($file, 'r');
    $content = fread($handle, 1024);
} finally {
    if (isset($handle)) {
        fclose($handle);
    }
}

// Result::bracket equivalent
$result = Result::bracket(
    acquire: fn () => fopen($file, 'r'),
    use: fn ($handle) => fread($handle, 1024),
    release: fn ($handle) => fclose($handle)
);
```

### Multiple Resources (Nested Brackets):

```php
$result = Result::bracket(
    acquire: fn () => new PDO($dsn, $user, $pass),
    use: fn (PDO $db) =>
        Result::bracket(
            acquire: fn () => fopen('/export.csv', 'w'),
            use: fn ($file) => (
                $users = $db->query('SELECT * FROM users')->fetchAll(),
                fwrite($file, json_encode($users)),
                $users
            )[2],
            release: fn ($file) => fclose($file)
        ),
    release: fn (PDO $db) => $db = null
);

$result->match(
    onSuccess: fn ($users) => print("✅ Exported " . count($users) . " users"),
    onFail: fn ($error) => print("❌ {$error}")
);
```

### Real-World: Temporary File Processing

```php
$result = Result::bracket(
    acquire: fn () => tempnam(sys_get_temp_dir(), 'upload_'),
    use: fn ($tmpFile) => (
        file_put_contents($tmpFile, $uploadedFileContent),
        // Process file
        validateAndMove($tmpFile, $finalPath),
        true
    )[2],
    release: fn ($tmpFile) => 
        is_file($tmpFile) && unlink($tmpFile)  // Always cleanup temp file
);

$result->match(
    onSuccess: fn () => print("✅ File processed and moved"),
    onFail: fn ($error) => print("❌ {$error}\n")
);

// Temp file is ALWAYS deleted, even if validation fails
```

### Error Handling in bracket():

```php
$result = Result::bracket(
    acquire: fn () => fopen($file, 'r'),
    use: fn ($handle) => 
        throw new Exception("Processing failed!"),  // ← Fails here
    release: fn ($handle) => 
        fclose($handle)  // ← Still runs!
);

// Result contains the "Processing failed!" error
// But file was closed before error propagated
// DB connection released even if query fails
```

## See Also

- [Kitchen sink overview](./)
- [Retry builder](./retry-builder)
