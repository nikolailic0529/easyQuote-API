<?php

namespace App\Domain\Worldwide\Policies;

use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorldwideDistributionPolicy
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

        if ($user->can('view_own_ww_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, WorldwideDistribution $worldwideDistribution)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_own_ww_quotes')) {
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

        if ($user->can('create_ww_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, WorldwideDistribution $worldwideDistribution)
    {
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, WorldwideDistribution $worldwideDistribution)
    {
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return mixed
     */
    public function restore(User $user, WorldwideDistribution $worldwideDistribution)
    {
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return mixed
     */
    public function forceDelete(User $user, WorldwideDistribution $worldwideDistribution)
    {
    }
}
