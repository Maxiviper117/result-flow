<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

/**
 * @return Result<array{id: int, account_id: int}, string>
 */
function findExampleUser(int $userId): Result
{
    if ($userId !== 42) {
        return Result::fail('User not found', ['step' => 'user.lookup']);
    }

    return Result::ok(
        ['id' => $userId, 'account_id' => 9001],
        ['step' => 'user.lookup'],
    );
}

/**
 * @return Result<array{id: int, user_id: int}, string>
 */
function findExampleAccount(int $accountId, int $userId): Result
{
    return Result::ok(
        ['id' => $accountId, 'user_id' => $userId],
        ['step' => 'account.lookup'],
    );
}

function printExampleResult(string $label, Result $result): void
{
    echo "\n{$label}:\n";
    echo json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
}

$success = Result::flow(function (): Generator {
    $user = yield findExampleUser(42);
    $account = yield findExampleAccount($user['account_id'], $user['id']);

    return [
        'user' => $user,
        'account' => $account,
    ];
});

$failure = Result::flow(function (): Generator {
    $user = yield findExampleUser(7);

    return $user;
});

printExampleResult('Successful workflow', $success);
printExampleResult('Short-circuited workflow', $failure);
