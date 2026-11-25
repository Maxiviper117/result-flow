<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Result;

/** @return Result<int, string> @phpstan-impure */
function saveResult(): Result
{
    return Result::ok(1);
}
/** @param Result<int, string> $result */
function acceptResult(Result $result): void {}

/** @return Result<int, string> */
function forwardResult(): Result
{
    return saveResult();
}
final class Action
{
    /** @return Result<int, string> @phpstan-impure */
    public function save(): Result
    {
        return Result::fail('bad');
    }

    /** @return Result<int, string> @phpstan-impure */
    public static function create(): Result
    {
        return Result::ok(1);
    }
}

saveResult();
(new Action)->save();
Action::create();
$assigned = saveResult();
acceptResult(saveResult());
if (saveResult()->isFail()) {
    echo 'failed';
}
if (random_int(0, 1) === 1) {
    acceptResult(saveResult());
}
// @phpstan-ignore resultFlow.ignoredResult
saveResult();
