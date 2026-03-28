<?php

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Support\Errors\Cause;
use Maxiviper117\ResultFlow\Support\Errors\DataTaggedError;

class ReviewTestError extends DataTaggedError
{
    public const CODE = 'E_REVIEW';
}

class AnotherReviewTestError extends DataTaggedError
{
    public const CODE = 'E_REVIEW_ALT';
}

class MissingCodeReviewError extends DataTaggedError {}

describe('DataTaggedError and Cause integration', function () {
    it('serializes DataTaggedError with nested Cause to array and JSON', function () {
        $child = new Cause('E_DB', 'DB failed', ['query' => 'INSERT']);
        $cause = new Cause('E_PERSIST', 'Persist failed', [], [$child]);

        $err = new DataTaggedError('E_NOT_FOUND', 'Not found', ['id' => 42], $cause);
        $result = Result::fail($err);

        $arr = $result->toArray();

        expect($arr['ok'])->toBeFalse();
        expect($arr['error'])->toBe($err->toArray());

        $json = $result->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['error']['code'])->toBe('E_NOT_FOUND');
        expect($decoded['error']['cause']['code'])->toBe('E_PERSIST');
        expect($decoded['error']['cause']['causes'][0]['code'])->toBe('E_DB');
    });

    it('provides debug fields including error_code and message', function () {
        $err = new DataTaggedError('E_X', 'Something broke', null);
        $result = Result::fail($err);

        $debug = $result->toDebugArray();

        expect($debug['error_code'])->toBe('E_X');
        expect($debug['error_message'])->toBe('Something broke');
    });

    it('can match by error class using matchError', function () {
        $err = new ReviewTestError('E_HANDLE', 'Handle me', null);
        $result = Result::fail($err);

        $out = $result->matchError([
            ReviewTestError::class => function ($e) {
                return 'handled:'.$e->code();
            },
        ], fn ($v) => 'ok', fn ($e) => 'unhandled');

        expect($out)->toBe('handled:E_HANDLE');
    });

    it('throwIfFail throws the DataTaggedError (it is Throwable)', function () {
        $err = new DataTaggedError('E_THROW', 'Throw me');
        $result = Result::fail($err);

        expect(function () use ($result) {
            $result->throwIfFail();
        })->toThrow(DataTaggedError::class);
    });

    it('can recover by class using catchError', function () {
        $result = Result::fail(ReviewTestError::from('Recover me'));

        $recovered = $result->catchError([
            ReviewTestError::class => fn (ReviewTestError $e) => 'recovered:'.$e->code(),
        ]);

        expect($recovered->isOk())->toBeTrue();
        expect($recovered->value())->toBe('recovered:E_REVIEW');
    });

    it('returns the original failed result when no class handler matches and no fallback is provided', function () {
        $result = Result::fail(AnotherReviewTestError::from('No handler'));

        $unchanged = $result->catchError([
            ReviewTestError::class => fn (ReviewTestError $e) => 'wrong',
        ]);

        expect($unchanged->isFail())->toBeTrue();
        expect($unchanged->error())->toBeInstanceOf(AnotherReviewTestError::class);
    });

    it('uses fallback when failure is not a ResultError', function () {
        $result = Result::fail('legacy-error');

        $handled = $result->catchError([
            ReviewTestError::class => fn (ReviewTestError $e) => 'wrong',
        ], fn ($error) => 'fallback:'.$error);

        expect($handled->isOk())->toBeTrue();
        expect($handled->value())->toBe('fallback:legacy-error');
    });

    it('preserves failure when catchError handler returns a failed Result', function () {
        $result = Result::fail(ReviewTestError::from('Refail'));

        $handled = $result->catchError([
            ReviewTestError::class => fn (ReviewTestError $e) => Result::fail(AnotherReviewTestError::from('Still failing')),
        ]);

        expect($handled->isFail())->toBeTrue();
        expect($handled->error())->toBeInstanceOf(AnotherReviewTestError::class);
    });

    it('can create named errors from subclass CODE constants', function () {
        $error = ReviewTestError::from('Created from constant', ['x' => 1]);

        expect($error)->toBeInstanceOf(ReviewTestError::class);
        expect($error->code())->toBe('E_REVIEW');
        expect($error->payload())->toBe(['x' => 1]);
    });

    it('throws when named constructor is used without a subclass CODE constant', function () {
        expect(fn () => MissingCodeReviewError::from('Missing code'))->toThrow(LogicException::class);
    });
});
