<?php

namespace App\Observers\Collaboration;

use App\App\Models\User;

class UserObserver
{
    /**
     * Handle the user "created" event.
     *
     * @param  \App\App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        cache()->tags('users')->flush();
    }

    /**
     * Handle the user "deleted" event.
     *
     * @param  \App\App\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        cache()->tags('users')->flush();
    }
}
