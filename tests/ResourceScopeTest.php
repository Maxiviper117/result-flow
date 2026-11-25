<?php

use Maxiviper117\ResultFlow\ResourceScope;
use Maxiviper117\ResultFlow\Result;

it('bracket returns acquire failure and does not call release', function () {
    $released = false;

    $result = ResourceScope::run(
        acquire: fn () => Result::fail('acquire failed', ['from' => 'acquire']),
        use: fn ($resource) => Result::ok($resource),
        release: function () use (&$released): void {
            $released = true;
        }
    );

    expect($result->isFail())->toBeTrue();
    expect($result->match(fn ($value) => null, fn ($error) => $error))->toBe('acquire failed');
    expect($result->meta())->toBe(['from' => 'acquire']);
    expect($released)->toBeFalse();
});

it('bracket converts acquire exception to failure and does not call release', function () {
    $released = false;

    $result = ResourceScope::run(
        acquire: function () {
            throw new RuntimeException('acquire boom');
        },
        use: fn ($resource) => Result::ok($resource),
        release: function () use (&$released): void {
            $released = true;
        }
    );

    expect($result->isFail())->toBeTrue();
    expect($result->match(fn ($value) => null, fn ($error) => $error))->toBeInstanceOf(RuntimeException::class);
    expect($released)->toBeFalse();
});

it('bracket succeeds when use and release succeed', function () {
    $releasedResource = null;

    $result = ResourceScope::run(
        acquire: fn () => fopen('php://temp', 'r+'),
        use: function ($handle) {
            fwrite($handle, 'hello');
            rewind($handle);

            return fread($handle, 5);
        },
        release: function ($handle) use (&$releasedResource): void {
            $releasedResource = $handle;
            fclose($handle);
        }
    );

    expect($result->isOk())->toBeTrue();
    expect($result->match(fn ($value) => $value, fn ($error) => null))->toBe('hello');
    expect($releasedResource)->not->toBeNull();
});

it('bracket keeps use failure when release succeeds', function () {
    $released = false;

    $result = ResourceScope::run(
        acquire: fn () => 'resource',
        use: fn ($resource) => Result::fail('use failed', ['phase' => 'use']),
        release: function ($resource) use (&$released): void {
            $released = true;
        }
    );

    expect($result->isFail())->toBeTrue();
    expect($result->match(fn ($value) => null, fn ($error) => $error))->toBe('use failed');
    expect($result->meta())->toBe(['phase' => 'use']);
    expect($released)->toBeTrue();
});

it('bracket keeps use failure and stores release exception metadata', function () {
    $result = ResourceScope::run(
        acquire: fn () => 'resource',
        use: fn ($resource) => Result::fail('use failed', ['phase' => 'use']),
        release: function ($resource): void {
            throw new RuntimeException('release boom');
        }
    );

    expect($result->isFail())->toBeTrue();
    expect($result->match(fn ($value) => null, fn ($error) => $error))->toBe('use failed');
    expect($result->meta())->toHaveKey('phase');
    expect($result->meta())->toHaveKey('bracket.release_exception');
    expect($result->meta()['bracket.release_exception'])->toBeInstanceOf(RuntimeException::class);
});

it('bracket fails with release exception when use succeeds', function () {
    $result = ResourceScope::run(
        acquire: fn () => 'resource',
        use: fn ($resource) => Result::ok('done', ['phase' => 'use']),
        release: function ($resource): void {
            throw new RuntimeException('release failed');
        }
    );

    expect($result->isFail())->toBeTrue();
    expect($result->match(fn ($value) => null, fn ($error) => $error))->toBeInstanceOf(RuntimeException::class);
    expect($result->match(fn ($value) => null, fn ($error) => $error)->getMessage())->toBe('release failed');
    expect($result->meta())->toBe(['phase' => 'use']);
});

it('bracket supports plain values and Result returns for acquire and use', function () {
    $fromPlain = ResourceScope::run(
        acquire: fn () => 5,
        use: fn (int $resource) => $resource * 2,
        release: fn (int $resource) => null
    );

    $fromResult = ResourceScope::run(
        acquire: fn () => Result::ok(10),
        use: fn (int $resource) => Result::ok($resource + 1),
        release: fn (int $resource) => null
    );

    expect($fromPlain->isOk())->toBeTrue();
    expect($fromPlain->match(fn ($value) => $value, fn ($error) => null))->toBe(10);
    expect($fromResult->isOk())->toBeTrue();
    expect($fromResult->match(fn ($value) => $value, fn ($error) => null))->toBe(11);
});

it('releases exactly once when use throws and preserves the original exception', function () {
    $failure = new RuntimeException('use failed');
    $released = 0;
    $resource = new stdClass;

    $result = ResourceScope::run(
        acquire: fn () => $resource,
        use: fn () => throw $failure,
        release: function ($received) use (&$released, $resource): void {
            expect($received)->toBe($resource);
            $released++;
        },
    );

    expect($released)->toBe(1)
        ->and($result->match(fn ($value) => null, fn ($error) => $error))->toBe($failure);
});

it('retains both exceptions when use and release throw', function () {
    $useFailure = new RuntimeException('use failed');
    $releaseFailure = new RuntimeException('release failed');
    $released = 0;

    $result = ResourceScope::run(
        acquire: fn () => 'resource',
        use: fn () => throw $useFailure,
        release: function () use (&$released, $releaseFailure): void {
            $released++;
            throw $releaseFailure;
        },
    );

    expect($released)->toBe(1)
        ->and($result->match(fn ($value) => null, fn ($error) => $error))->toBe($useFailure)
        ->and($result->meta()['bracket.release_exception'])->toBe($releaseFailure);
});
