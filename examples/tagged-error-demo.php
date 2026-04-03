<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\Cause;
use Maxiviper117\ResultFlow\Support\Errors\DataTaggedError;

/*
|--------------------------------------------------------------------------
| Named domain errors
|--------------------------------------------------------------------------
*/

final class UserPersistError extends DataTaggedError
{
    public const CODE = 'E_USER_PERSIST';
}

final class UserValidationError extends DataTaggedError
{
    public const CODE = 'E_USER_VALIDATE';
}

/*
|--------------------------------------------------------------------------
| Demo function returning named error classes
|--------------------------------------------------------------------------
*/

/**
 * Create a user for demo purposes.
 *
 * @param 'persist'|'validate'|'ok' $mode
 * @return Result<array{id: int, email: string}, UserPersistError|UserValidationError>
 */
function createUser(string $mode): Result
{
    if ($mode === 'persist') {
        $cause = new Cause(
            code: 'E_DB',
            message: 'Primary key violation',
            metadata: [
                'table' => 'users',
                'constraint' => 'email_unique',
            ],
            causes: [
                new Cause(
                    code: 'E_SQL',
                    message: 'SQL error 1062',
                    metadata: [
                        'sqlState' => '23000',
                        'errorCode' => 1062,
                    ],
                ),
            ],
        );

        return Result::fail(
            new UserPersistError(
                code: UserPersistError::CODE,
                message: 'Unable to persist user',
                payload: ['email' => 'jane@example.com'],
                cause: $cause,
            )
        );
    }

    if ($mode === 'validate') {
        return Result::fail(
            new UserValidationError(
                code: UserValidationError::CODE,
                message: 'User data is invalid',
                payload: [
                    'email' => 'not-an-email',
                    'field' => 'email',
                ],
            )
        );
    }

    return Result::ok([
        'id' => 123,
        'email' => 'john@example.com',
    ]);
}

/*
|--------------------------------------------------------------------------
| Example 1: Persist failure
|--------------------------------------------------------------------------
*/

/** @var Result<array{id:int,email:string}, UserPersistError|UserValidationError> $persistResult */
$persistResult = createUser('persist');

echo "Persist failure JSON:\n" . $persistResult->toJson(JSON_PRETTY_PRINT) . "\n\n";

echo "Persist failure debug:\n";
print_r($persistResult->toDebugArray());

$matchedPersist = $persistResult->matchError(
    [
        UserPersistError::class => fn(UserPersistError $e) => 'matched persist: ' . $e->code(),
        UserValidationError::class => fn(UserValidationError $e) => 'matched validation: ' . $e->code(),
    ],
    fn($user) => 'ok: ' . $user['email'],
    fn($error) => 'unhandled'
);

echo "\nmatchError (persist): {$matchedPersist}\n";

$recoveredPersist = $persistResult->catchError(
    [
        UserPersistError::class => fn(UserPersistError $e) => [
            'id' => 999,
            'email' => 'recovered-from-persist@example.com',
        ],
    ],
    fn($error) => Result::fail($error)
);

echo "\ncatchError (persist) ->\n";
print_r($recoveredPersist->toArray());

/*
|--------------------------------------------------------------------------
| Example 2: Validation failure
|--------------------------------------------------------------------------
*/

/** @var Result<array{id:int,email:string}, UserPersistError|UserValidationError> $validationResult */
$validationResult = createUser('validate');

echo "\nValidation failure JSON:\n" . $validationResult->toJson(JSON_PRETTY_PRINT) . "\n\n";

$matchedValidation = $validationResult->matchError(
    [
        UserPersistError::class => fn(UserPersistError $e) => 'matched persist: ' . $e->code(),
        UserValidationError::class => fn(UserValidationError $e, array $meta) => 'matched validation: ' . $e->code(),
    ],
    fn($user) => 'ok: ' . $user['email'],
    fn($error) => 'unhandled'
);

echo "matchError (validate): {$matchedValidation}\n";

$recoveredValidation = $validationResult->catchError(
    [
        UserValidationError::class => fn(UserValidationError $e) => [
            'id' => 1000,
            'email' => 'recovered-from-validation@example.com',
        ],
    ]
);

echo "\ncatchError (validate) ->\n";
print_r($recoveredValidation->toArray());

/*
|--------------------------------------------------------------------------
| Example 3: Success path
|--------------------------------------------------------------------------
*/

/** @var Result<array{id:int,email:string}, UserPersistError|UserValidationError> $okResult */
$okResult = createUser('ok');

echo "\nSuccess JSON:\n" . $okResult->toJson(JSON_PRETTY_PRINT) . "\n\n";

$matchedOk = $okResult->matchError(
    [
        UserPersistError::class => fn(UserPersistError $e) => 'matched persist: ' . $e->code(),
        UserValidationError::class => fn(UserValidationError $e) => 'matched validation: ' . $e->code(),
    ],
    fn($user) => 'ok user: ' . $user['email'],
    fn($error) => 'unhandled'
);

echo "matchError (ok): {$matchedOk}\n";

$recoveredOk = $okResult->catchError(
    [
        UserPersistError::class => fn(UserPersistError $e) => ['id' => 0, 'email' => 'should-not-run@example.com'],
        UserValidationError::class => fn(UserValidationError $e) => ['id' => 0, 'email' => 'should-not-run@example.com'],
    ]
);

echo "\ncatchError (ok) ->\n";
print_r($recoveredOk->toArray());
