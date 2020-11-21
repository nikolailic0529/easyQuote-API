<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Quote\Discount\SND;
use App\Models\User;

class SNDPolicy
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

        if ($user->can('view_sn_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount\SND  $snd
     * @return mixed
     */
    public function view(User $user, SND $snd)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_sn_discounts')) {
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

        if ($user->can('create_sn_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount\SND  $snd
     * @return mixed
     */
    public function update(User $user, SND $snd)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('update_sn_discounts') &&
            $user->getKey() === $snd->{$snd->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Discount\SND  $SND
     * @return mixed
     */
    public function delete(User $user, SND $snd)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('delete_sn_discounts') &&
            $user->getKey() === $snd->{$snd->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }
}
