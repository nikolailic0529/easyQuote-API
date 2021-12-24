<?php

namespace App\Policies;

use App\Models\{
    HpeContract,
    User
};
use Illuminate\Auth\Access\HandlesAuthorization;

class HpeContractPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contracts.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->hasAnyPermission('view_contracts', 'view_own_contracts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the contract.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\HpeContract  $contract
     * @return mixed
     */
    public function view(User $user, HpeContract $contract)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_own_contracts') && $user->getKey() === $contract->user_id) {
            return true;
        }
    }

    /**
     * Determine whether the user can create contracts.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_contracts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the contract.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\HpeContract  $contract
     * @return mixed
     */
    public function update(User $user, HpeContract $contract)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('update_own_contracts') && $user->getKey() === $contract->user_id) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the contract.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\HpeContract  $contract
     * @return mixed
     */
    public function delete(User $user, HpeContract $contract)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('delete_own_contracts') && $user->getKey() === $contract->user_id) {
            return true;
        }
    }

    /**
     * Determine whether the user can make a new copy of the contract.
     *
     * @param  User $user
     * @param  HpeContract $contract
     * @return mixed
     */
    public function copy(User $user, HpeContract $contract)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('update_own_contracts')) {
            return true;
        }
    }
}
