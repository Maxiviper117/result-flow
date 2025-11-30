<?php

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Maxiviper117\ResultFlow\Laravel\ResultFlowServiceProvider;
use Maxiviper117\ResultFlow\Tests\Support\FakeApplication;

it('merges config defaults and registers publishable config', function () {
    IlluminateServiceProvider::$publishes = [];
    IlluminateServiceProvider::$publishGroups = [];

    $app = new FakeApplication(inConsole: true);
    $provider = new ResultFlowServiceProvider($app);

    $provider->register();

    $config = $app->config->get('result-flow');
    expect($config)->toBeArray();
    expect($config['debug']['redaction'])->toBe('***REDACTED***');

    $provider->boot();

    $paths = IlluminateServiceProvider::pathsToPublish(ResultFlowServiceProvider::class, 'result-flow-config');

    $publishedSource = array_key_first($paths);
    $publishedTarget = $paths[$publishedSource] ?? null;

    $expectedSource = dirname((new \ReflectionClass(ResultFlowServiceProvider::class))->getFileName(), 3) . '/config/result-flow.php';
    $expectedTarget = config_path('result-flow.php');

    expect(realpath($publishedSource))->toBe(realpath($expectedSource));
    expect($publishedTarget)->toBe($expectedTarget);
});
