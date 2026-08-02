# Generator-Based Composition Plan for Result Flow

## Implementation status

The initial runtime release and the defined deferred extensions are implemented.

### Implemented

- [x] Add `Result::flow()` for synchronous generator workflows.
- [x] Add the internal `Support\Operations\Flow` runner.
- [x] Send successful yielded values back into the generator.
- [x] Stop at the first failed yielded `Result`.
- [x] Reject callbacks that do not return a `Generator`.
- [x] Reject yielded values that are not `Result` instances.
- [x] Wrap plain final values in `Result::ok(...)`.
- [x] Support final returned `Result` values.
- [x] Support empty generators and implicit `null` returns.
- [x] Allow unexpected exceptions to escape.
- [x] Merge metadata with the documented precedence order.
- [x] Run generator cleanup after success, failure, and exceptions.
- [x] Add PHPDoc generics and a valid PHPStan fixture.
- [x] Add runtime tests in `tests/FlowTest.php`.
- [x] Add user documentation and runnable examples.
- [x] Update the public API whitelist and Laravel Boost guidance.
- [x] Add `Result::tryFlow()` for explicit exception capture.
- [x] Add a PHPStan rule for invalid yields and unsupported `yield from` forms.
- [x] Add failure-union inference for flow callbacks.
- [x] Add yielded-value inference through `Result::bind()`.
- [x] Add `Result::bind()` for typed nested composition.
- [x] Add a flow benchmark for 1, 5, 10, and 50 steps.

### Deferred

- [ ] Add asynchronous runtime support.
- [ ] Add parallel execution support.
- [ ] Add cancellation support.
- [ ] Add automatic retries inside the flow runner.

The remaining deferred items require public API contracts that this plan does not define.

## 1. Objective

Add an optional generator-based composition API that allows sequential Result operations to be written in an imperative style.

The feature should improve readability for workflows where later operations depend on several earlier successful values.

Existing fluent methods such as `map()`, `then()`, and `flatMap()` must remain fully supported.

### Target usage

```php
$result = Result::flow(function (): Generator {
    $user = yield findUser($userId);
    $account = yield findAccount($user->accountId);
    $permissions = yield loadPermissions($user->id);

    $invoice = yield createInvoice(
        $user,
        $account,
        $permissions,
    );

    return $invoice;
});
```

Expected behavior:

* Each yielded value must be a `Result`.
* Successful values are sent back into the generator.
* The first failure stops the workflow.
* The failure is returned without running later steps.
* A plain final return value becomes `Result::ok($value)`.
* A final returned `Result` is returned directly.
* Unexpected exceptions escape by default.
* Metadata is propagated according to an explicit policy.

---

# 2. Scope

## Included in the first release

* `Result::flow()`
* Synchronous PHP generators
* Fail-fast composition
* Plain final return values
* Final returned Result values
* Yield validation
* Metadata propagation
* PHPStan-compatible PHPDoc
* Runtime tests
* Static-analysis tests
* Documentation and examples

## Not included initially

* Asynchronous runtimes such as Amp or ReactPHP
* Cancellation
* Parallel execution
* Automatic retries inside the flow runner
* Yielding plain values
* Yielding callables
* Yielding promises or futures
* `yield from` nested workflow syntax
* Automatic typed error-union inference
* Effect-style services or dependency injection
* Automatic exception capture

Keeping the first version strict reduces ambiguity and makes future changes safer.

---

# 3. API Design

## Recommended method name

```php
Result::flow()
```

This name fits the package and describes workflow composition clearly.

Avoid `defer()` because the workflow begins immediately.

Avoid `run()` because it is too generic.

Avoid `gen()` because it is less descriptive to PHP developers unfamiliar with Effect-style APIs.

## Initial signature

```php
/**
 * Execute a generator-based Result workflow.
 *
 * Each yielded value must be a Result.
 * Successful values are sent back into the generator.
 * The first failure short-circuits the workflow.
 *
 * @template TSuccess
 * @template TFailure
 *
 * @param callable(): Generator<mixed, Result<mixed, TFailure>, mixed, TSuccess|Result<TSuccess, TFailure>> $workflow
 * @return Result<TSuccess, TFailure>
 */
public static function flow(callable $workflow): self
```

The actual PHPStan signature may require refinement after prototyping.

## Optional exception-capturing variant

Do not add this until the base API is stable.

Possible future API:

```php
Result::tryFlow()
```

Behavior:

```php
/**
 * @return Result<TSuccess, TFailure|Throwable>
 */
```

The distinction should remain clear:

* `flow()` allows unexpected exceptions to escape.
* `tryFlow()` captures exceptions into the failure channel.

This prevents programming errors from silently becoming recoverable business failures.

---

# 4. Runtime Semantics

## 4.1 Workflow creation

The callback must return a `Generator`.

Invalid:

```php
Result::flow(fn () => Result::ok('value'));
```

Recommended runtime error:

```php
LogicException(
    'Result::flow() callback must return a Generator.'
);
```

The runtime exception should identify misuse of the API rather than represent a normal Result failure.

## 4.2 Yield rules

Every yielded value must be a `Result`.

Valid:

```php
$user = yield findUser($id);
```

Invalid:

```php
$user = yield $repository->find($id);
```

Invalid:

```php
yield 123;
```

Recommended runtime error:

```php
LogicException(
    sprintf(
        'Result::flow() expected a Result, %s yielded.',
        get_debug_type($yielded),
    ),
);
```

## 4.3 Successful yield

Given:

```php
yield Result::ok($user);
```

The runner sends `$user` back into the generator:

```php
$generator->send($yielded->value());
```

The assigned variable receives the success value:

```php
$user = yield findUser($id);
```

## 4.4 Failed yield

Given:

```php
yield Result::fail(new UserNotFound($id));
```

The runner:

1. Stops advancing the generator.
2. Returns the failed Result.
3. Does not execute later workflow statements.
4. Preserves the failure payload.
5. Preserves accumulated metadata according to the metadata policy.

## 4.5 Final return value

A plain value becomes a successful Result:

```php
return $invoice;
```

Equivalent output:

```php
Result::ok($invoice);
```

A final returned Result remains unchanged:

```php
return createInvoice($user);
```

This permits the final operation to return its Result directly.

## 4.6 Empty generator

A generator that never yields but returns a value should be supported:

```php
Result::flow(function (): Generator {
    if (false) {
        yield;
    }

    return 42;
});
```

Output:

```php
Result::ok(42);
```

This is an edge case rather than a recommended usage pattern.

## 4.7 No return statement

A generator with no explicit return produces `null`.

The initial design should normalize this to:

```php
Result::ok(null);
```

This follows normal PHP generator return behavior.

Documentation should recommend explicit returns.

---

# 5. Proposed Runtime Implementation

Create a dedicated internal operation instead of placing the entire algorithm directly in `Result.php`.

Suggested structure:

```text
src/
├── Result.php
└── Support/
    └── Operations/
        └── Flow.php
```

## Internal runner

```php
<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Operations;

use Generator;
use LogicException;
use Maxiviper117\ResultFlow\Result;

final class Flow
{
    /**
     * @template TSuccess
     * @template TFailure
     *
     * @param callable(): Generator $workflow
     * @return Result<TSuccess, TFailure>
     */
    public static function run(callable $workflow): Result
    {
        $generator = $workflow();

        if (!$generator instanceof Generator) {
            throw new LogicException(
                'Result::flow() callback must return a Generator.',
            );
        }

        $metadata = [];

        while ($generator->valid()) {
            $yielded = $generator->current();

            if (!$yielded instanceof Result) {
                throw new LogicException(
                    sprintf(
                        'Result::flow() expected a Result, %s yielded.',
                        get_debug_type($yielded),
                    ),
                );
            }

            $metadata = self::mergeMetadata(
                $metadata,
                $yielded->meta(),
            );

            if ($yielded->isFail()) {
                return Result::fail(
                    $yielded->error(),
                    self::mergeFailureMetadata(
                        $metadata,
                        $yielded->meta(),
                    ),
                );
            }

            $generator->send($yielded->value());
        }

        $returned = $generator->getReturn();

        if ($returned instanceof Result) {
            return $returned->mergeMeta($metadata);
        }

        return Result::ok($returned, $metadata);
    }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $next
     * @return array<string, mixed>
     */
    private static function mergeMetadata(
        array $existing,
        array $next,
    ): array {
        return array_replace($existing, $next);
    }

    /**
     * @param array<string, mixed> $accumulated
     * @param array<string, mixed> $failure
     * @return array<string, mixed>
     */
    private static function mergeFailureMetadata(
        array $accumulated,
        array $failure,
    ): array {
        return array_replace($accumulated, $failure);
    }
}
```

## Public Result method

```php
/**
 * @template TSuccess
 * @template TFailure
 *
 * @param callable(): Generator $workflow
 * @return Result<TSuccess, TFailure>
 */
public static function flow(callable $workflow): self
{
    return Flow::run($workflow);
}
```

The implementation should be adjusted after static-analysis experiments.

---

# 6. Metadata Policy

Metadata behavior must be decided before the feature is released.

## Recommended initial policy

* Metadata from each successful yielded Result is accumulated.
* Later metadata keys overwrite earlier matching keys.
* Failure metadata has highest priority.
* Final returned Result metadata has highest priority over accumulated success metadata.

Example:

```php
$result = Result::flow(function (): Generator {
    $user = yield Result::ok(
        new User(1),
        ['request_id' => 'r-1', 'step' => 'user'],
    );

    $account = yield Result::ok(
        new Account(2),
        ['step' => 'account'],
    );

    return Result::ok(
        [$user, $account],
        ['completed' => true],
    );
});
```

Expected metadata:

```php
[
    'request_id' => 'r-1',
    'step' => 'account',
    'completed' => true,
]
```

## Precedence order

From lowest to highest priority:

1. Earlier successful yields
2. Later successful yields
3. Failed yield
4. Final returned Result

## Collision concern

Overwriting keys can hide earlier metadata.

Document that callers should use namespaced keys:

```php
[
    'user.lookup.duration_ms' => 12,
    'account.lookup.duration_ms' => 18,
]
```

Do not introduce a complex step metadata hierarchy in the first version.

A future opt-in tracing mode could preserve step-by-step metadata.

---

# 7. Exception Policy

## Default behavior

`Result::flow()` must not catch unexpected exceptions.

Example:

```php
Result::flow(function (): Generator {
    $user = yield findUser(1);

    throw new LogicException('Broken invariant');
});
```

The `LogicException` should escape.

This makes defects visible and prevents accidental recovery from programming errors.

## Expected exceptions

Operations that intentionally convert exceptions should do so before yielding:

```php
$response = yield Result::of(
    fn () => $client->request($request),
);
```

or:

```php
$response = yield Result::defer(
    fn () => $client->request($request),
);
```

This keeps exception conversion explicit.

## Future `tryFlow()`

After `flow()` is stable, consider:

```php
Result::tryFlow(function (): Generator {
    // ...
});
```

That method could wrap the full workflow and return:

```php
Result<TSuccess, TFailure|Throwable>
```

Do not include it in the first implementation unless there is a demonstrated use case.

---

# 8. Generator Cleanup Behavior

PHP generators may contain `finally` blocks:

```php
Result::flow(function (): Generator {
    $handle = fopen('/tmp/file.txt', 'r');

    try {
        $data = yield readData($handle);

        return $data;
    } finally {
        fclose($handle);
    }
});
```

Short-circuiting on failure must allow generator cleanup to run.

This requires explicit consideration because returning immediately from the runner may leave the generator suspended.

## Required behavior

When a yielded Result fails:

1. Preserve the failed Result.
2. Close or terminate the generator.
3. Ensure generator `finally` blocks execute.
4. Return the failure afterward.

A robust implementation may need to destroy or close the generator reference before returning.

PHP does not expose a direct public `Generator::close()` method in modern versions. Cleanup normally occurs when the generator is destroyed.

A safer structure may be:

```php
$failure = null;

while ($generator->valid()) {
    $yielded = $generator->current();

    if ($yielded->isFail()) {
        $failure = $yielded;
        break;
    }

    $generator->send($yielded->value());
}

if ($failure !== null) {
    unset($generator);

    return $failure;
}
```

This behavior must be verified with tests.

## Required cleanup tests

* `finally` runs after a yielded failure.
* `finally` runs after a successful workflow.
* `finally` runs after an exception.
* Nested `try/finally` blocks run in the expected order.
* Cleanup exceptions are not silently discarded.

Do not claim resource safety until these cases are tested.

For critical resources, `Result::bracket()` should remain the recommended API.

---

# 9. PHPStan Extension Plan

The generator runtime can be added without a PHPStan extension, but the static-analysis experience may initially be limited.

## Phase 1 PHPStan support

Use PHPDoc generics and confirm whether PHPStan can infer:

```php
$user = yield findUser($id);
```

The likely initial result is that `$user` will be inferred as `mixed` or as the generator send type.

Run experiments before designing the extension.

## Phase 2 custom rule

Add a PHPStan rule that validates yielded values inside `Result::flow()`.

Example diagnostic:

```text
Result::flow() must yield Result values. User yielded.
```

The rule should detect:

* integers
* strings
* arrays
* arbitrary objects
* nullable Results
* unions containing non-Result values
* unsupported `yield from`

## Phase 3 return type extension

Implement a dynamic static method return type extension for:

```php
Result::flow(...)
```

Goals:

* Infer the final success type.
* Collect failure types from yielded Results.
* Include the failure type from a final returned Result.
* Produce a union of all possible failure types.

Example:

```php
Result<User, UserNotFound>
Result<Account, AccountNotFound>
Result<Invoice, InvoiceFailed>
```

Desired output:

```php
Result<
    Invoice,
    UserNotFound|AccountNotFound|InvoiceFailed
>
```

## Phase 4 local yielded-value inference

Investigate whether a stable public PHPStan extension can infer:

```php
$user = yield findUser($id);
```

as:

```php
User
```

This is the most uncertain part.

Possible approaches:

* `ExpressionTypeResolverExtension`
* custom virtual nodes
* scope-aware AST preprocessing
* an explicit typed binding helper

## Fallback typed binding API

If raw yield inference cannot be implemented reliably, consider:

```php
$user = yield from Result::bind(findUser($id));
```

Possible helper:

```php
/**
 * @template TSuccess
 * @template TFailure
 *
 * @param Result<TSuccess, TFailure> $result
 * @return Generator<int, Result<TSuccess, TFailure>, TSuccess, TSuccess>
 */
public static function bind(Result $result): Generator
{
    /** @var TSuccess $value */
    $value = yield $result;

    return $value;
}
```

This should only be adopted if it materially improves PHPStan and IDE inference.

Do not add `bind()` merely for visual similarity with other languages.

---

# 10. Static Analysis Test Matrix

Create a dedicated PHPStan fixture directory:

```text
tests/
└── PHPStan/
    ├── valid-flow.php
    ├── invalid-yield.php
    ├── success-inference.php
    ├── failure-union.php
    ├── final-result.php
    └── metadata.php
```

## Valid examples

```php
$result = Result::flow(function (): Generator {
    $user = yield findUser(1);

    return $user->name;
});
```

## Invalid yield

```php
Result::flow(function (): Generator {
    yield 'not a result';

    return null;
});
```

## Union failure inference

```php
$result = Result::flow(function (): Generator {
    $user = yield findUser(1);
    $account = yield findAccount($user->accountId);

    return createInvoice($account);
});
```

Expected inferred failure type:

```php
UserNotFound|AccountNotFound|InvoiceFailed
```

## Ignored Result diagnostics

A later plugin improvement may warn when the flow result is ignored:

```php
Result::flow(function (): Generator {
    // ...
});
```

This is valuable but should not block the initial release.

---

# 11. Runtime Test Plan

Create:

```text
tests/
└── Unit/
    └── FlowTest.php
```

## Core behavior

### Successful single yield

```php
it('sends a successful value back into the generator');
```

### Multiple successful yields

```php
it('composes multiple result operations');
```

### First failure short-circuits

```php
it('returns the first failure');
```

### Later operations are not called

```php
it('does not execute statements after a failed yield');
```

### Plain final value

```php
it('wraps a plain final value in success');
```

### Final Result

```php
it('returns a final result');
```

### Empty workflow

```php
it('supports a generator with no active yields');
```

### Null return

```php
it('returns a successful null when no value is returned');
```

## Invalid usage

### Callback does not return Generator

```php
it('rejects a non-generator workflow');
```

### Yielded value is not Result

```php
it('rejects a yielded non-result value');
```

### Nullable yield

```php
it('rejects null yielded from a workflow');
```

## Exception behavior

### Exception before first yield

```php
it('allows exceptions before the first yield to escape');
```

### Exception after successful yield

```php
it('allows exceptions after a successful yield to escape');
```

### Exception from generator return

```php
it('allows return-expression exceptions to escape');
```

## Metadata behavior

### Metadata accumulation

```php
it('accumulates metadata from successful yields');
```

### Later metadata overwrites earlier metadata

```php
it('uses later metadata for duplicate keys');
```

### Failure metadata wins

```php
it('gives failure metadata highest priority');
```

### Final Result metadata wins

```php
it('gives final result metadata highest priority');
```

## Cleanup behavior

### Finally runs on success

```php
it('executes generator cleanup after success');
```

### Finally runs on failure

```php
it('executes generator cleanup after short-circuit');
```

### Finally runs on exception

```php
it('executes generator cleanup after an exception');
```

---

# 12. Integration Test Examples

Use realistic workflows rather than only synthetic integer operations.

## User and account lookup

```php
$result = Result::flow(function (): Generator {
    $user = yield findUser($userId);
    $account = yield findAccount($user->accountId);

    return new UserAccountView(
        user: $user,
        account: $account,
    );
});
```

## API synchronization

```php
$result = Result::flow(function (): Generator {
    $candidate = yield loadCandidate($candidateId);
    $payload = yield buildCandidatePayload($candidate);
    $response = yield sendCandidateToWorkable($payload);

    return yield saveExternalCandidateId(
        $candidate,
        $response->candidateId,
    );
});
```

## Invoice creation

```php
$result = Result::flow(function (): Generator {
    $purchaseOrder = yield loadPurchaseOrder($purchaseOrderId);
    $existingInvoice = yield findMonthlyInvoice(
        $purchaseOrder,
        $month,
    );

    if ($existingInvoice !== null) {
        return yield updateInvoice(
            $existingInvoice,
            $purchaseOrder,
        );
    }

    return yield createInvoice(
        $purchaseOrder,
        $month,
    );
});
```

These examples should be used to judge whether the API genuinely improves readability.

---

# 13. Documentation Plan

Add a dedicated guide:

```text
docs/
└── guides/
    └── generator-composition.md
```

## Required sections

1. What generator composition solves
2. Basic example
3. Short-circuit behavior
4. Returning values and Results
5. Metadata behavior
6. Exception behavior
7. Cleanup limitations
8. When to use `flow()`
9. When to use fluent chaining
10. PHPStan limitations
11. Unsupported yield forms
12. Migration examples

## Fluent versus generator example

### Fluent

```php
$result = findUser($userId)
    ->then(fn (User $user) =>
        findAccount($user->accountId)
            ->then(fn (Account $account) =>
                createInvoice($user, $account)
            )
    );
```

### Generator

```php
$result = Result::flow(function (): Generator {
    $user = yield findUser($userId);
    $account = yield findAccount($user->accountId);

    return createInvoice($user, $account);
});
```

## Usage guidance

Recommend `flow()` for:

* orchestration services
* use cases
* multi-step integrations
* workflows needing several earlier values
* transaction-like application logic

Recommend fluent methods for:

* simple transformations
* validation chains
* mapping one value
* error mapping
* short two-step operations

---

# 14. Public API Updates

Update the public API whitelist with:

```text
Static constructors and utilities:
- flow
```

Do not add aliases such as:

```php
Result::gen()
Result::do()
Result::workflow()
Result::compose()
```

One name is enough.

Update Laravel Boost guidance so generated code:

* uses `Result::flow()` only for multi-step workflows
* does not replace short fluent chains unnecessarily
* yields only Result values
* handles the returned Result at the application boundary

---

# 15. Backward Compatibility

The feature should be fully additive.

It must not change:

* `Result::ok()`
* `Result::fail()`
* `then()`
* `flatMap()`
* metadata behavior outside flows
* exception behavior outside flows
* retry behavior
* bracket behavior
* Laravel response handling

No major version bump should be required if the API is additive and does not alter existing behavior.

A minor version release is appropriate.

---

# 16. Performance Considerations

Generator composition will have slightly more overhead than direct fluent chaining due to:

* generator creation
* repeated `current()`
* repeated `send()`
* runtime yield validation
* metadata merging

For normal application orchestration, this overhead should be negligible compared with:

* database calls
* network requests
* filesystem access
* serialization
* framework bootstrapping

Do not position `flow()` as a performance optimization.

Add a small benchmark only to confirm there is no pathological implementation issue.

Suggested benchmark:

```text
1, 5, 10, and 50 successful steps
fluent chain versus generator flow
metadata disabled versus metadata populated
```

Performance should not be the main acceptance criterion.

---

# 17. Release Phases

## Phase 1: Runtime prototype

Implement a private prototype outside the public API.

Goals:

* validate generator stepping
* validate failure short-circuiting
* verify `finally` cleanup
* test plain and Result returns
* determine metadata behavior

Deliverable:

```text
Internal proof of concept with focused tests
```

## Phase 2: Public experimental API

Add:

```php
Result::flow()
```

Include:

* runtime validation
* complete unit tests
* documentation
* explicit PHPStan limitations

Mark it as experimental in documentation, not through unstable runtime annotations.

Deliverable:

```text
Minor release with generator composition
```

## Phase 3: PHPStan validation rule

Add static checks for:

* non-Result yields
* unsupported `yield from`
* invalid callback return
* invalid final return if restrictions are introduced

Deliverable:

```text
Bundled or separate PHPStan extension
```

## Phase 4: Type inference prototype

Investigate:

* yielded success-value inference
* accumulated failure unions
* final success inference
* nested Result flows

Deliverable:

```text
Decision document on raw yield versus bind helper
```

## Phase 5: Stabilization

Review real usage after several workflows have been implemented.

Questions:

* Does it reduce nesting?
* Are error types preserved?
* Is metadata behavior predictable?
* Are IDE warnings understandable?
* Are developers misusing it for simple mappings?
* Does cleanup behave reliably?

Remove the experimental documentation label after these are answered.

---

# 18. Acceptance Criteria

The feature is ready for an initial release when all of the following are true:

* A workflow can yield multiple Result values.
* Successful values are sent back correctly.
* The first failure short-circuits execution.
* Later workflow code does not run after failure.
* Plain final values become successful Results.
* Final returned Results are supported.
* Invalid yielded values produce clear errors.
* Exceptions escape by default.
* Metadata precedence is documented and tested.
* Generator cleanup behavior is verified.
* Existing fluent APIs remain unchanged.
* PHPStan does not report errors for documented valid examples.
* Documentation explains when not to use the feature.
* At least three realistic workflows are clearer than their fluent equivalents.

---

# 19. Risks

## Weak local type inference

PHPStan may infer yielded assignments as `mixed`.

Mitigation:

* prototype before finalizing the syntax
* investigate a PHPStan extension
* consider `yield from Result::bind()` only if necessary
* document temporary annotations where unavoidable

## Hidden metadata collisions

Merged metadata keys may overwrite each other.

Mitigation:

* document precedence
* recommend namespaced keys
* add tests for collisions

## Generator cleanup uncertainty

Short-circuiting may not immediately execute `finally`.

Mitigation:

* test generator destruction behavior
* retain `bracket()` as the preferred critical-resource API
* do not claim complete resource safety without evidence

## API becoming too broad

Generator composition could encourage more workflow features on the core Result class.

Mitigation:

* keep the runner in `Support\Operations\Flow`
* expose only `Result::flow()`
* reject async, parallel, and effect-system features
* keep PHPStan support separate from runtime behavior

## Defects becoming ordinary failures

Capturing every exception would hide programming problems.

Mitigation:

* do not catch exceptions in `flow()`
* add `tryFlow()` only as an explicit future feature

---

# 20. Recommended First Pull Request

The first pull request should contain only:

1. `Support\Operations\Flow`
2. `Result::flow()`
3. Runtime validation
4. Fail-fast composition
5. Plain and Result final returns
6. Metadata propagation
7. Unit tests
8. Generator cleanup tests
9. Basic documentation
10. Public API whitelist update
11. Laravel Boost guidance update

The first pull request should not contain:

* a PHPStan extension
* `Result::bind()`
* `tryFlow()`
* nested flow syntax
* async integration
* custom tracing
* alternate method aliases

This keeps the runtime feature independently reviewable.

---

# 21. Final Recommendation

Implement generator composition as an optional, narrowly scoped feature.

Start with:

```php
Result::flow(function (): Generator {
    $first = yield operationOne();
    $second = yield operationTwo($first);

    return operationThree($first, $second);
});
```

Keep these rules fixed:

* yield only Result values
* fail on the first failure
* return plain values or Results
* propagate metadata predictably
* allow unexpected exceptions to escape
* preserve the fluent API
* do not add async or Effect-style runtime concepts

Build the runtime API first. Treat advanced PHPStan inference as a separate project because it has greater technical uncertainty than the generator runner itself.
