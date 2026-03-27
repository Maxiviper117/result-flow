<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Tests\Support;

use Maxiviper117\ResultFlow\Result;

final class ResultCoreCallableArrayService
{
    public array $calledWith = [];

    public function handle($value, $meta)
    {
        $this->calledWith = [$value, $meta];

        return Result::ok($value + 5, array_merge($meta, ['from' => 'service']));
    }
}

final class ResultCorePipelineDto
{
    public function __construct(
        public string $name,
        public string $sku,
        public int $price,
        public string $description,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => $this->price,
            'description' => $this->description,
        ];
    }
}
