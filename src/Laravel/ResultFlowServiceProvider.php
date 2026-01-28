<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Laravel;

use Illuminate\Support\ServiceProvider;

/**
 * Laravel service provider for ResultFlow configuration publishing.
 */
class ResultFlowServiceProvider extends ServiceProvider
{
    /**
     * Register package configuration.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/result-flow.php',
            'result-flow'
        );
    }

    /**
     * Bootstrap package configuration publishing.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $target = function_exists('config_path')
                ? config_path('result-flow.php')
                : (__DIR__.'/../../config/result-flow.php'); // fallback for non-Laravel contexts / static analysis

            $this->publishes([
                __DIR__.'/../../config/result-flow.php' => $target,
            ], 'result-flow-config');
        }
    }
}
