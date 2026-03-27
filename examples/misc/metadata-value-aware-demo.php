<?php

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

function randomResult(): Result
{
    if (random_int(0, 1) === 1) {
        return Result::ok(['name' => 'Alice', 'roles' => ['admin', 'user']], ['request_id' => 'r-123']);
    }

    return Result::fail('validation_failed', ['request_id' => 'r-456']);
}

// Random result path
$result = randomResult();

if ($result->isOk()) {
    echo "Result is OK\n";
} else {
    echo "Result is FAIL\n";
}

// Tap into metadata for logging or side effects
$result->tapMeta(function (array $meta, $value) {
    echo "Logging metadata: ".json_encode($meta)."\n";
    echo "Value: ".json_encode($value)."\n";
});

// Map metadata to transform it
$result = $result->mapMeta(function (array $meta, $value) {
    // Add a timestamp to the metadata
    return array_merge($meta, ['timestamp' => time()]);
});

// Merge additional metadata
$result = $result->mergeMeta(function (array $meta, $value) {
    echo "Merging additional metadata based on value: ".json_encode($value)."\n";
    return ['processed_by' => 'metadata-value-aware-demo.php'];
});