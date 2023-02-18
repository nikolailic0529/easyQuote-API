<?php

namespace App\Domain\Notification\Providers;

use App\Domain\Notification\Channels\CustomDatabaseChannel;
use App\Domain\Notification\Contracts\NotificationFactory;
use App\Domain\Notification\Contracts\NotificationRepositoryInterface;
use App\Domain\Notification\Listeners\EnsureNotificationCanBeSent;
use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Observers\NotificationObserver;
use App\Domain\Notification\Repositories\NotificationRepository;
use App\Domain\Notification\Services\DefaultNotificationFactory;
use App\Domain\Notification\Services\NotificationSettingsPresenter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationRepositoryInterface::class, NotificationRepository::class);

        $this->app->alias(NotificationRepositoryInterface::class, 'notification.repository');

        $this->app->bind(NotificationFactory::class, DefaultNotificationFactory::class);

        $this->app->alias(NotificationFactory::class, 'notification.factory');

        $this->app->when(NotificationSettingsPresenter::class)
            ->needs('$config')
            ->giveConfig('notification');

        $this->app->when(EnsureNotificationCanBeSent::class)
            ->needs('$config')
            ->giveConfig('notification');
    }

    public function boot(): void
    {
        $this->registerChannels();

        Notification::observe(NotificationObserver::class);
    }

    protected function registerChannels(): void
    {
        /** @var ChannelManager $manager */
        $manager = $this->app->make(ChannelManager::class);

        $manager->extend('database.custom', static function (Container $container): CustomDatabaseChannel {
            return $container->make(CustomDatabaseChannel::class);
        });
    }

    public function provides(): array
    {
        return [
            NotificationRepositoryInterface::class,
            'notification.repository',
            NotificationFactory::class,
            'notification.factory',
        ];
    }
}
