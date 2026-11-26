<?php

declare(strict_types=1);

$process = proc_open(
    [PHP_BINARY, __DIR__.'/../vendor/bin/phpstan', 'analyse', '-c', __DIR__.'/../phpstan-unused-result-test.neon', '--no-progress', '--error-format=json'],
    [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    dirname(__DIR__),
);
if (! is_resource($process)) {
    throw new RuntimeException('Could not start PHPStan');
}
$output = stream_get_contents($pipes[1]);
$errors = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$status = proc_close($process);
$data = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
$messages = array_merge(...array_map(fn ($file) => $file['messages'], array_values($data['files'])));
if ($status !== 1 || $data['totals']['errors'] !== 0 || count($messages) !== 3
    || array_column($messages, 'identifier') !== array_fill(0, 3, 'resultFlow.ignoredResult')) {
    throw new RuntimeException("Unexpected PHPStan fixture result: {$output}\n{$errors}");
}
echo "Unused Result rule fixtures passed.\n";
