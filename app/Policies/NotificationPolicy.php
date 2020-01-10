<?php

namespace App\Policies;

use App\Models\System\Notification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any notifications.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the notification.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\System\Notification  $notification
     * @return mixed
     */
    public function view(User $user, Notification $notification)
    {
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can delete the notification.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\System\Notification  $notification
     * @return mixed
     */
    public function delete(User $user, Notification $notification)
    {
        return $user->id === $notification->user_id;
    }

    /**
     * Determine whether the user can delete all the own notifications.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function deleteAll(User $user)
    {
        return true;
    }
}
