<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Tests\Types;

use JsonException;
use Maxiviper117\ResultFlow\Result;

use function PHPStan\Testing\assertType;

final class ValidationError {}
final class WriteError {}
final class DomainError {}
final class Order {}

/** @return Result<int, ValidationError> */
function validate(string $input): Result
{
    if ($input === '') {
        return Result::fail(new ValidationError);
    }

    return Result::ok(strlen($input));
}
/** @return Result<Order, WriteError> */
function save(int $input): Result
{
    return Result::ok(new Order);
}

/** @return Result<string, DomainError> */
function recover(ValidationError|WriteError $error): Result
{
    return $error instanceof ValidationError ? Result::ok('fallback') : Result::fail(new DomainError);
}

$result = Result::ok('input')->andThen(validate(...))->andThen(save(...));
assertType('Maxiviper117\\ResultFlow\\Result<Maxiviper117\\ResultFlow\\Tests\\Types\\Order, Maxiviper117\\ResultFlow\\Tests\\Types\\ValidationError|Maxiviper117\\ResultFlow\\Tests\\Types\\WriteError>', $result);
$normalized = $result->mapError(fn (ValidationError|WriteError $error): DomainError => new DomainError);
assertType('Maxiviper117\\ResultFlow\\Result<Maxiviper117\\ResultFlow\\Tests\\Types\\Order, Maxiviper117\\ResultFlow\\Tests\\Types\\DomainError>', $normalized);
$recovered = $result->orElse(fn (ValidationError|WriteError $error): Result => Result::ok('fallback'));
assertType('Maxiviper117\\ResultFlow\\Result<Maxiviper117\\ResultFlow\\Tests\\Types\\Order|string, never>', $recovered);
$possiblyFailedRecovery = $result->orElse(recover(...));
assertType('Maxiviper117\\ResultFlow\\Result<Maxiviper117\\ResultFlow\\Tests\\Types\\Order|string, Maxiviper117\\ResultFlow\\Tests\\Types\\DomainError>', $possiblyFailedRecovery);
$matched = $normalized->match(fn (Order $order): int => 1, fn (DomainError $error): string => 'failure');
assertType("1|'failure'", $matched);
$attempt = Result::attempt(fn (): int => 1, catch: JsonException::class);
assertType('Maxiviper117\\ResultFlow\\Result<int, JsonException>', $attempt);
/** @param Result<null, null> $nullable */
function checkNullable(Result $nullable): void
{
    assertType('null', $nullable->match(fn ($value) => $value, fn ($error) => $error));
}
