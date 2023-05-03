<?php

namespace App\Foundation\Log\Providers;

use App\Domain\Log\Contracts\Logger;
use App\Domain\Log\CustomLogger;
use App\Foundation\Http\Middleware\HttpResponseLogger;
use App\Foundation\Log\HttpLogger\HttpLogWriter;
use Illuminate\Support\ServiceProvider;

class LoggerServiceProvider extends ServiceProvider
{
    public function register(): void
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

    public function boot(): void
    {
    }

    protected function registerHelpers()
    {
        require_once base_path('bootstrap/loghelpers.php');
    }
}
