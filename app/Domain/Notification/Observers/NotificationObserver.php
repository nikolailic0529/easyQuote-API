<?php

namespace App\Domain\Notification\Observers;

use App\Domain\Notification\Events\NotificationCreated;
use App\Domain\Notification\Events\NotificationDeleted;
use App\Domain\Notification\Events\{
    NotificationRead
};
use App\Domain\Notification\Models\Notification;

class NotificationObserver
{
    /**
     * Handle the notification "created" event.
     *
     * @return void
     */
    public function created(Notification $notification)
    {
        event(new NotificationCreated($notification));
    }

    /**
     * Handle the notification "read" event.
     *
     * @return void
     */
    public function read(Notification $notification)
    {
        event(new NotificationRead($notification));
    }

    /**
     * Handle the notification "deleted" event.
     *
     * @return void
     */
    public function deleted(Notification $notification)
    {
        event(new NotificationDeleted($notification));
    }
}
