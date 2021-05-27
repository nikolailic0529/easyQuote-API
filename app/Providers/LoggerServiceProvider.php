<?php

namespace App\Providers;

use App\Contracts\Services\Logger;
use App\Log\HttpLogWriter;
use App\Services\CustomLogger;
use Illuminate\Support\ServiceProvider;

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

        $this->app->bind(HttpLogWriter::class, function () {
            return new HttpLogWriter($this->app['log']->channel('http-requests'));
        });

        $this->registerHelpers();
    }

    protected function registerHelpers()
    {
        require_once base_path('bootstrap/loghelpers.php');
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
}
