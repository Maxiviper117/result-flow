<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Output\DebugOutput;
use Maxiviper117\ResultFlow\Result;

// Composer development dependencies can supply mbstring polyfills.
require __DIR__.'/../../src/Result.php';
require __DIR__.'/../../src/Support/Output/Debug.php';
require __DIR__.'/../../src/Output/DebugOutput.php';

if (function_exists('mb_strlen') || function_exists('mb_substr')) {
    throw new RuntimeException('Test requires the multibyte functions to be unavailable');
}

$atLimit = str_repeat('a', 199).'é';
$debug = DebugOutput::toArray(Result::fail($atLimit.'!', ['caption' => $atLimit]));

echo json_encode($debug, JSON_THROW_ON_ERROR);
