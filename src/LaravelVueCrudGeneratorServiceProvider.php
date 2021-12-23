<?php

namespace Arturssmirnovs\LaravelVueCrudGenerator;

use Illuminate\Support\ServiceProvider;

class LaravelVueCrudGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole())
        {
            $this->commands([
                LaravelVueCrudGenerator::class,
            ]);
        }
    }
}