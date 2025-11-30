<?php

declare(strict_types=1);

namespace Illuminate\Support;

use function array_key_exists;
use function array_merge;
use function is_array;
use function is_file;

// Provide a minimal stub when Illuminate is not installed (e.g., in unit tests).
if (class_exists(ServiceProvider::class)) {
    return;
}

abstract class ServiceProvider
{
    public static array $publishes = [];
    public static array $publishGroups = [];

    public function __construct(protected $app = null)
    {
    }

    protected function mergeConfigFrom(string $path, string $key): void
    {
        if (! $this->app || ($this->app->configurationIsCached() ?? false)) {
            return;
        }

        if (! is_file($path)) {
            return;
        }

        $configRepo = $this->app->make('config');
        $existing = is_array($configRepo?->get($key, [])) ? $configRepo->get($key, []) : [];
        $values = require $path;

        if (! is_array($values)) {
            return;
        }

        $configRepo?->set($key, array_merge($values, $existing));
    }

    protected function publishes(array $paths, ?string $group = null): void
    {
        static::$publishes[static::class] = array_merge(static::$publishes[static::class] ?? [], $paths);

        if ($group) {
            static::$publishGroups[$group][static::class] = static::$publishes[static::class];
        }
    }

    public static function pathsToPublish($provider = null, $group = null): array
    {
        if ($provider && $group) {
            return static::$publishGroups[$group][$provider] ?? [];
        }

        if ($group && array_key_exists($group, static::$publishGroups)) {
            return static::$publishGroups[$group];
        }

        if ($provider && array_key_exists($provider, static::$publishes)) {
            return static::$publishes[$provider];
        }

        if ($group || $provider) {
            return [];
        }

        $paths = [];

        foreach (static::$publishes as $publish) {
            $paths = array_merge($paths, $publish);
        }

        return $paths;
    }
}
