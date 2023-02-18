<?php

namespace App\Domain\Margin\Policies;

use App\Domain\Margin\Models\Margin;
use App\Domain\User\Models\{
    User
};
use Illuminate\Auth\Access\HandlesAuthorization;

class MarginPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any margins.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_margins')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the margin.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return mixed
     */
    public function view(User $user, Margin $margin)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_margins')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create margins.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_margins')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the margin.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return mixed
     */
    public function update(User $user, Margin $margin)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('update_margins') &&
            $user->getKey() === $margin->{$margin->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the margin.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return mixed
     */
    public function delete(User $user, Margin $margin)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('delete_margins') &&
            $user->getKey() === $margin->{$margin->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }
}
