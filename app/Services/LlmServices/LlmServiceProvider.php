<?php

namespace App\Services\LlmServices;

use App\Services\LlmServices\Functions\CreateEventTool;
use Illuminate\Support\ServiceProvider;

class LlmServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind('llm_driver', function () {
            return new LlmDriverClient;
        });

        $this->app->bind('create_event_tool', function () {
            return new CreateEventTool();
        });

    }
}
