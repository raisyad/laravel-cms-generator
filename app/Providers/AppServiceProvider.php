<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Support\Scaffold\TableIntrospector::class);
        $this->app->singleton(\App\Support\Scaffold\StubRenderer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\MakeCmsCommand::class,
                \App\Console\Commands\MakeApiCommand::class,
            ]);
        }
    }
}
