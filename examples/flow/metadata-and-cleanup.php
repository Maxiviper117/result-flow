<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

$cleanup = [];

$result = Result::flow(function () use (&$cleanup): \Generator {
    try {
        $user = yield Result::ok(
            ['id' => 42],
            [
                'request_id' => 'request-123',
                'step' => 'user.lookup',
            ],
        );

        yield Result::fail(
            'Permissions unavailable',
            ['step' => 'permissions.lookup'],
        );

        return $user;
    } finally {
        $cleanup[] = 'workflow cleanup ran';
    }
});

echo "Result:\n";
echo json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
echo "Cleanup:\n";
echo json_encode($cleanup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
