<?php

namespace App\Domain\Activity\Policies;

use App\Domain\Activity\Models\Activity;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any activities.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('view_activities');
    }

    /**
     * Determine whether the user can view the activity.
     *
     * @return mixed
     */
    public function view(User $user, Activity $activity)
    {
        return $user->can('view_activities');
    }
}
