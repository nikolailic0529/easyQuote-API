<?php

namespace App\Domain\HpeContract\Policies;

use App\Domain\HpeContract\Models\HpeContract;
use App\Domain\User\Models\{
    User
};
use Illuminate\Auth\Access\HandlesAuthorization;

class HpeContractPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contracts.
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

        if ($user->hasAnyPermission('view_contracts', 'view_own_contracts')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the contract.
     *
     * @param \App\Domain\User\Models\User $user
     *
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
     * @param \App\Domain\User\Models\User $user
     *
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
     * @param \App\Domain\User\Models\User $user
     *
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
     * @param \App\Domain\User\Models\User $user
     *
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
     * @param \App\Domain\User\Models\User $user
     *
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
