<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\System\NotificationRepositoryInterface;
use App\Contracts\Services\NotificationInterface;
use App\Repositories\System\NotificationRepository;
use App\Services\NotificationDispatcher;

class NotificationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(NotificationRepositoryInterface::class, NotificationRepository::class);

        $this->app->bind(NotificationInterface::class, NotificationDispatcher::class);

        $this->app->alias(NotificationRepositoryInterface::class, 'notification.repository');

        $this->app->alias(NotificationInterface::class, 'notification.dispatcher');
    }

    public function provides()
    {
        return [
            NotificationRepositoryInterface::class,
            'notification.repository',
            NotificationInterface::class,
            'notification.dispatcher',
        ];
    }
}
