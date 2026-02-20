<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

echo "--- Doodle Scratchpad ---\n";

$result = Result::ok(['message' => 'Edit this file for quick experiments'])
    ->map(fn (array $payload) => array_merge($payload, ['note' => 'customize-me']));

echo json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
