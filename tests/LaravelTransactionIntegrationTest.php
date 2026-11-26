<?php

declare(strict_types=1);

use Illuminate\Database\SQLiteConnection;
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\UnwrapFailure;

it('rolls back a real Laravel transaction when a failure is unwrapped after a write', function () {
    $connection = new SQLiteConnection(new PDO('sqlite::memory:'));
    $connection->statement('create table orders (id integer primary key, name text not null)');
    $error = (object) ['code' => 'invalid'];

    try {
        $connection->transaction(function () use ($connection, $error) {
            $connection->insert('insert into orders (id, name) values (?, ?)', [1, 'draft']);

            return Result::fail($error)->unwrap();
        });
        test()->fail('Expected a failure to cause rollback');
    } catch (UnwrapFailure $caught) {
        expect($caught->failure)->toBe($error);
    }

    expect($connection->select('select * from orders'))->toBe([])
        ->and($connection->transactionLevel())->toBe(0);
});

it('commits a real Laravel transaction when a failure is returned normally', function () {
    $connection = new SQLiteConnection(new PDO('sqlite::memory:'));
    $connection->statement('create table orders (id integer primary key)');
    $result = $connection->transaction(function () use ($connection) {
        $connection->insert('insert into orders (id) values (?)', [1]);

        return Result::fail('invalid');
    });

    expect($result->isFail())->toBeTrue()->and($connection->select('select * from orders'))->toHaveCount(1);
});

it('rolls back a nested transaction when the signal reaches Laravel', function () {
    $connection = new SQLiteConnection(new PDO('sqlite::memory:'));
    $connection->statement('create table orders (id integer primary key)');
    $connection->transaction(function () use ($connection) {
        $connection->insert('insert into orders (id) values (?)', [1]);
        try {
            $connection->transaction(function () use ($connection) {
                $connection->insert('insert into orders (id) values (?)', [2]);
                Result::fail('invalid')->unwrap();
            });
        } catch (UnwrapFailure $caught) {
            expect($caught->failure)->toBe('invalid');
        }
    });

    expect(array_column($connection->select('select id from orders'), 'id'))->toBe([1]);
});
