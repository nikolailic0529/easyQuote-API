<?php

namespace App\Domain\Notification\Providers;

use App\Domain\Notification\Listeners\EnsureNotificationCanBeSent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Notifications\Events\NotificationSending;

class NotificationEventServiceProvider extends EventServiceProvider
{
    protected $listen = [
        NotificationSending::class => [
            EnsureNotificationCanBeSent::class,
        ],
    ];
}
