#!/usr/bin/env php
<?php

declare(strict_types=1);

$command = 'php vendor/bin/pest --coverage';

if (PHP_OS_FAMILY === 'Windows') {
    $psScript = <<<PS
\$ErrorActionPreference = 'Stop'
\$env:XDEBUG_MODE = 'coverage'
$command
Remove-Item Env:\\XDEBUG_MODE -ErrorAction SilentlyContinue
PS;

    $tmp = sys_get_temp_dir() . '\\pest-coverage.ps1';
    file_put_contents($tmp, $psScript);

    $fullCommand = 'powershell -NoProfile -ExecutionPolicy Bypass -File ' . escapeshellarg($tmp);
} else {
    $fullCommand = 'XDEBUG_MODE=coverage ' . $command;
}

passthru($fullCommand, $exitCode);

// Cleanup
if (isset($tmp) && file_exists($tmp)) {
    @unlink($tmp);
}

exit($exitCode);
