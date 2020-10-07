<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Services\Logger;
use App\Services\CustomLogger;

class LoggerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Logger::class, CustomLogger::class);

        $this->app->alias(Logger::class, 'customlogger');

        $this->registerHelpers();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    protected function registerHelpers()
    {
        require_once base_path('bootstrap/loghelpers.php');
    }
}
