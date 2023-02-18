<?php

namespace App\Domain\Log\Providers;

use Devengine\LogKeeper\Repositories\LocalLogFileRepository;
use Devengine\LogKeeper\Repositories\LogFileRepository;
use Devengine\LogKeeper\Services\LogKeeperService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\FilesystemInterface;

class LogKeeperServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LogFileRepository::class, LocalLogFileRepository::class);

        $this->app->when(LocalLogFileRepository::class)->needs(FilesystemInterface::class)->give(function (Container $container) {
            return $container['filesystem']->disk('logs')->getDriver();
        });

        $this->app->bind(LogKeeperService::class, function (Container $container) {
            return new LogKeeperService(
                config: $container['config']['log-keeper'],
                repository: $container[LogFileRepository::class],
            );
        });
    }

    public function boot(): void
    {
    }
}
