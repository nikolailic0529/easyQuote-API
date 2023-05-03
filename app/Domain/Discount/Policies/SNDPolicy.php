<?php

namespace App\Domain\Discount\Policies;

use App\Domain\Discount\Models\SND;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SNDPolicy
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

        if ($user->can('view_sn_discounts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
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
     * @param \App\Domain\Discount\Models\SND $SND
     *
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
