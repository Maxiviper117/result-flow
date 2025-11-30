<?php

declare(strict_types=1);

namespace Maxiviper117\ResultFlow\Laravel;

use Illuminate\Support\ServiceProvider;

class ResultFlowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/result-flow.php',
            'result-flow'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/result-flow.php' => config_path('result-flow.php'),
            ], 'result-flow-config');
        }
    }
}
