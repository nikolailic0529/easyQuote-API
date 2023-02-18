<?php

namespace App\Domain\Team\Policies;

use App\Domain\Team\Models\Team;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
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
