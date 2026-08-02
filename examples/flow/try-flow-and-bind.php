<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\Result;

function printTryFlowExampleResult(string $label, Result $result): void
{
    $output = $result->toArray();

    if ($output['error'] instanceof Throwable) {
        $output['error'] = [
            'type' => $output['error']::class,
            'message' => $output['error']->getMessage(),
        ];
    }

    echo "\n{$label}:\n";
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
}

$bound = Result::flow(function (): Generator {
    $user = yield from Result::bind(Result::ok(
        ['id' => 42],
        ['step' => 'user.lookup'],
    ));

    return ['user_id' => $user['id']];
});

$captured = Result::tryFlow(function (): Generator {
    yield Result::ok('before exception');

    throw new LogicException('unexpected workflow exception');
});

printTryFlowExampleResult('Typed bind workflow', $bound);
printTryFlowExampleResult('Captured workflow exception', $captured);
