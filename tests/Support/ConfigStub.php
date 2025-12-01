<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Tests\Support;

final class ConfigStub
{
    private static array $store = [];

    public static function set(string $key, array $value): void
    {
        self::$store[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$store[$key] ?? $default;
    }

    public static function reset(): void
    {
        self::$store = [];
    }
}
