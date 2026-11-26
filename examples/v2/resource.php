<?php

declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Maxiviper117\ResultFlow\ResourceScope;

$result = ResourceScope::run(
    acquire: fn () => fopen('php://temp', 'w+'),
    use: fn ($stream) => fwrite($stream, 'hello'),
    release: fn ($stream) => fclose($stream),
);
echo $result->match(fn ($bytes) => "Wrote $bytes bytes\n", fn ($error) => "Failed\n");
