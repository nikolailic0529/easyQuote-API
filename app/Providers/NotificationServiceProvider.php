<?php

namespace App\Providers;

use App\Contracts\Repositories\System\NotificationRepositoryInterface;
use App\Contracts\Services\NotificationFactory;
use App\Repositories\System\NotificationRepository;
use App\Services\Notification\DefaultNotificationFactory;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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

        $this->app->alias(NotificationRepositoryInterface::class, 'notification.repository');

        $this->app->bind(NotificationFactory::class, DefaultNotificationFactory::class);

        $this->app->alias(NotificationFactory::class, 'notification.factory');
    }

    public function provides()
    {
        return [
            NotificationRepositoryInterface::class,
            'notification.repository',
            NotificationFactory::class,
            'notification.factory',
        ];
    }
}
