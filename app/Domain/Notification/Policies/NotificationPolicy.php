<?php

namespace App\Domain\Notification\Policies;

use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any notifications.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the notification.
     *
     * @return mixed
     */
    public function view(User $user, Notification $notification)
    {
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can delete the notification.
     *
     * @return mixed
     */
    public function delete(User $user, Notification $notification)
    {
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can delete all the own notifications.
     *
     * @return mixed
     */
    public function deleteAll(User $user)
    {
        return true;
    }
}
