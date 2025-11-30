<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Tests\Support;

/**
 * Minimal Laravel-like application stub to exercise ServiceProvider behaviors.
 */
final class FakeApplication
{
    public FakeConfig $config;

    public function __construct(private bool $inConsole = true)
    {
        $this->config = new FakeConfig;
    }

    public function runningInConsole(): bool
    {
        return $this->inConsole;
    }

    public function configurationIsCached(): bool
    {
        return false;
    }

    public function make($abstract): mixed
    {
        if ($abstract === 'config') {
            return $this->config;
        }

        return null;
    }

    // No-op stubs to satisfy ServiceProvider expectations.
    public function call(callable $callback): void {}

    public function afterResolving($name, $callback = null): void {}

    public function resolved($name): bool
    {
        return false;
    }
}

final class FakeConfig
{
    private array $data = [];

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
        ConfigStub::set($key, $value);
    }
}
