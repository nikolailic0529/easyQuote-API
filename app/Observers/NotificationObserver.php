<?php

namespace App\Observers;

use App\Events\{
    NotificationCreated,
    NotificationDeleted,
    NotificationRead
};
use App\Models\System\Notification;

class NotificationObserver
{
    /**
     * Handle the notification "created" event.
     *
     * @param  \App\Models\System\Notification  $notification
     * @return void
     */
    public function created(Notification $notification)
    {
        event(new NotificationCreated($notification));
    }

    /**
     * Handle the notification "read" event.
     *
     * @param  \App\Models\System\Notification  $notification
     * @return void
     */
    public function read(Notification $notification)
    {
        event(new NotificationRead($notification));
    }

    /**
     * Handle the notification "deleted" event.
     *
     * @param  \App\Models\System\Notification  $notification
     * @return void
     */
    public function deleted(Notification $notification)
    {
        event(new NotificationDeleted($notification));
    }
}
