<?php

use Maxiviper117\ResultFlow\Result;

it('bracket returns acquire failure and does not call release', function () {
    $released = false;

    $result = Result::bracket(
        acquire: fn () => Result::fail('acquire failed', ['from' => 'acquire']),
        use: fn ($resource) => Result::ok($resource),
        release: function () use (&$released): void {
            $released = true;
        }
    );

    expect($result->isFail())->toBeTrue();
    expect($result->error())->toBe('acquire failed');
    expect($result->meta())->toBe(['from' => 'acquire']);
    expect($released)->toBeFalse();
});

it('bracket converts acquire exception to failure and does not call release', function () {
    $released = false;

    $result = Result::bracket(
        acquire: function () {
            throw new RuntimeException('acquire boom');
        },
        use: fn ($resource) => Result::ok($resource),
        release: function () use (&$released): void {
            $released = true;
        }
    );

    expect($result->isFail())->toBeTrue();
    expect($result->error())->toBeInstanceOf(RuntimeException::class);
    expect($released)->toBeFalse();
});

it('bracket succeeds when use and release succeed', function () {
    $releasedResource = null;

    $result = Result::bracket(
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
    expect($result->value())->toBe('hello');
    expect($releasedResource)->not->toBeNull();
});

it('bracket keeps use failure when release succeeds', function () {
    $released = false;

    $result = Result::bracket(
        acquire: fn () => 'resource',
        use: fn ($resource) => Result::fail('use failed', ['phase' => 'use']),
        release: function ($resource) use (&$released): void {
            $released = true;
        }
    );

    expect($result->isFail())->toBeTrue();
    expect($result->error())->toBe('use failed');
    expect($result->meta())->toBe(['phase' => 'use']);
    expect($released)->toBeTrue();
});

it('bracket keeps use failure and stores release exception metadata', function () {
    $result = Result::bracket(
        acquire: fn () => 'resource',
        use: fn ($resource) => Result::fail('use failed', ['phase' => 'use']),
        release: function ($resource): void {
            throw new RuntimeException('release boom');
        }
    );

    expect($result->isFail())->toBeTrue();
    expect($result->error())->toBe('use failed');
    expect($result->meta())->toHaveKey('phase');
    expect($result->meta())->toHaveKey('bracket.release_exception');
    expect($result->meta()['bracket.release_exception'])->toBeInstanceOf(RuntimeException::class);
});

it('bracket fails with release exception when use succeeds', function () {
    $result = Result::bracket(
        acquire: fn () => 'resource',
        use: fn ($resource) => Result::ok('done', ['phase' => 'use']),
        release: function ($resource): void {
            throw new RuntimeException('release failed');
        }
    );

    expect($result->isFail())->toBeTrue();
    expect($result->error())->toBeInstanceOf(RuntimeException::class);
    expect($result->error()->getMessage())->toBe('release failed');
    expect($result->meta())->toBe(['phase' => 'use']);
});

it('bracket supports plain values and Result returns for acquire and use', function () {
    $fromPlain = Result::bracket(
        acquire: fn () => 5,
        use: fn (int $resource) => $resource * 2,
        release: fn (int $resource) => null
    );

    $fromResult = Result::bracket(
        acquire: fn () => Result::ok(10),
        use: fn (int $resource) => Result::ok($resource + 1),
        release: fn (int $resource) => null
    );

    expect($fromPlain->isOk())->toBeTrue();
    expect($fromPlain->value())->toBe(10);
    expect($fromResult->isOk())->toBeTrue();
    expect($fromResult->value())->toBe(11);
});
