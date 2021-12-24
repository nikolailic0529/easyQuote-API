<?php

namespace App\Policies;

use App\Models\Quote\WorldwideDistribution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorldwideDistributionPolicy
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

        if ($user->can('view_own_ww_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideDistribution  $worldwideDistribution
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
     * @param  \App\Models\User  $user
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
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideDistribution  $worldwideDistribution
     * @return mixed
     */
    public function update(User $user, WorldwideDistribution $worldwideDistribution)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideDistribution  $worldwideDistribution
     * @return mixed
     */
    public function delete(User $user, WorldwideDistribution $worldwideDistribution)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideDistribution  $worldwideDistribution
     * @return mixed
     */
    public function restore(User $user, WorldwideDistribution $worldwideDistribution)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\WorldwideDistribution  $worldwideDistribution
     * @return mixed
     */
    public function forceDelete(User $user, WorldwideDistribution $worldwideDistribution)
    {
        //
    }
}
