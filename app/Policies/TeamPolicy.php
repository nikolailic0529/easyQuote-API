<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_teams')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Team  $team
     * @return mixed
     */
    public function view(User $user, Team $team)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_teams')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_teams')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Team  $team
     * @return mixed
     */
    public function update(User $user, Team $team)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('update_teams')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Team  $team
     * @return mixed
     */
    public function delete(User $user, Team $team)
    {
        if ($team->is_system) {
            return $this->deny('You can not delete the system defined Team.');
        }

        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('delete_teams')) {
            return true;
        }
    }
}
