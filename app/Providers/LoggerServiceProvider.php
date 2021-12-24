<?php

namespace App\Providers;

use App\Contracts\Services\Logger;
use App\Http\Middleware\HttpResponseLogger;
use App\Log\Http\HttpLogWriter;
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
            return new HttpLogWriter(
                logger: $this->app['log']->channel('http-requests'),
                config: $this->app['config'],
            );
        });

        $this->app->bind(HttpResponseLogger::class, function () {
            return new HttpResponseLogger(
                logger: $this->app['log']->channel('http-requests'),
                config: $this->app['config'],
            );
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
