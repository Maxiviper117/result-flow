<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Support\Output {
    function class_exists(string $class): bool
    {
        return false;
    }
}

namespace {
    use Maxiviper117\ResultFlow\Output\LegacyOutput;
    use Maxiviper117\ResultFlow\Result;

    require __DIR__.'/../../vendor/autoload.php';

    try {
        LegacyOutput::toXml(Result::ok(1));
        exit(1);
    } catch (RuntimeException $error) {
        echo $error->getMessage();
    }
}
