<?php

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

// Successful value path
$result = Result::ok(['name' => 'Alice', 'roles' => ['admin', 'user']], ['request_id' => 'r-123'])
    ->mapMeta(function (array $meta, array $value) {
        return [
            ...$meta,
            'role_count' => count($value['roles']),
            'name_tag' => strtolower($value['name']),
        ];
    })
    ->mergeMeta(function (array $meta, array $value) {
        return [
            'description' => sprintf('User %s with %d roles', $value['name'], count($value['roles'])),
        ];
    });

echo "OK value example:\n";
print_r($result->toArray());

// Failure path remains metadata-only
$failed = Result::fail('validation_failed', ['request_id' => 'r-456'])
    ->mergeMeta(function (array $meta) {
        return ['debug_note' => 'Value unavailable for failed result.'];
    })
    ->mapMeta(function (array $meta) {
        return [...$meta, 'handled' => true];
    });

echo "\nFailed value example:\n";
print_r($failed->toArray());
