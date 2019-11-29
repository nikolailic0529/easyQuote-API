<?php

namespace App\Policies;

use App\App\Models\System\Activity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any activities.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('view_activities');
    }

    /**
     * Determine whether the user can view the activity.
     *
     * @param  \App\Models\User  $user
     * @param  \App\App\Models\System\Activity  $activity
     * @return mixed
     */
    public function view(User $user, Activity $activity)
    {
        return $user->can('view_activities');
    }
}
