<?php

require __DIR__.'/../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\DataTaggedError;
use Maxiviper117\ResultFlow\Support\Errors\Cause;

// Minimal demo showing structured error, matching, and recovery
$cause = new Cause('E_DB', 'Primary key violation');
$err = new DataTaggedError('E_USER_PERSIST', 'Unable to create user', ['email' => 'jane@example.com'], $cause);
$result = Result::fail($err);

echo "JSON:\n".$result->toJson(JSON_PRETTY_PRINT)."\n\n";

echo "Debug:\n";
print_r($result->toDebugArray());

// match by class
$matched = $result->matchError([
    DataTaggedError::class => fn(DataTaggedError $e) => 'matched:'.$e->code(),
], fn() => 'ok', fn() => 'unhandled');

echo "\nmatchError: $matched\n";

// recover using catchError by class
$recovered = $result->catchError([
    DataTaggedError::class => fn(DataTaggedError $e) => 'recovered:'.$e->code(),
]);

echo "\ncatchError -> ";
print_r($recovered->toArray());

// --- Alternative opt-in: create a named domain error class and match by class ---
class UserPersistError extends DataTaggedError
{
    public const CODE = 'E_USER_PERSIST';
}

$userErr = UserPersistError::from('Could not create user (named class)', ['email' => 'john@example.com']);
$r3 = Result::fail($userErr);

echo "\nNamed-class JSON:\n".$r3->toJson(JSON_PRETTY_PRINT)."\n\n";

$matchByClass = $r3->matchError([
    UserPersistError::class => fn(UserPersistError $e) => 'matched_named:'.$e->code(),
], fn() => 'ok', fn() => 'unhandled');

echo "matchError by named class: $matchByClass\n";

// --- Add another named error and a function that may return either ---
class AnotherUserError extends DataTaggedError
{
    public const CODE = 'E_USER_ALT';
}

/**
 * Create a user (demo).
 *
 * @param bool $useAlternate If true, returns an error variant
 * @return Result<array{id: int, email: string}, AnotherUserError|UserPersistError>
 */
function createUser(bool $useAlternate = false): Result
{
    if (!$useAlternate) {
        return Result::fail(UserPersistError::from('User persist failed', ['step' => 'save']));
    }

    if ($useAlternate) {
        return Result::fail(AnotherUserError::from('Alternate user error', ['step' => 'validate']));
    }

    // Simulate a successful creation path when not using the alternate error
    return Result::ok(['id' => 123, 'email' => 'john@example.com']);
}

/** @var Result<array{id:int,email:string}, AnotherUserError|UserPersistError> $resA */
$resA = createUser(false);
/** @var Result<array{id:int,email:string}, AnotherUserError|UserPersistError> $resB */
$resB = createUser(true);

echo "\ncreateUser(false) -> ";
print_r($resA->toArray());
echo "createUser(true) -> ";
print_r($resB->toArray());

// Match either error by providing both classes
$handleA = $resA->matchError([
    UserPersistError::class => fn(UserPersistError $e) => 'handled_user_persist: '.$e->code(),
    AnotherUserError::class => fn(AnotherUserError $e) => 'handled_another: '.$e->code(),
], fn() => 'ok', fn() => 'unhandled');

$handleB = $resB->matchError([
    UserPersistError::class => fn(UserPersistError $e) => 'handled_user_persist: '.$e->code(),
    AnotherUserError::class => fn(AnotherUserError $e) => 'handled_another: '.$e->code(),
], fn() => 'ok', fn() => 'unhandled');

echo "\nmatch createUser(false): $handleA\n";
echo "match createUser(true): $handleB\n";
