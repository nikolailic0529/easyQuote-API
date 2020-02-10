<?php

namespace App\Policies;

use App\Models\Quote\Contract;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
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
        return $user->can('view_contracts') || $user->can('view_own_contracts');
    }

    /**
     * Determine whether the user can view the contract.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Contract  $contract
     * @return mixed
     */
    public function view(User $user, Contract $contract)
    {
        if ($user->can('view_contracts')) {
            return true;
        }

        if ($user->can('view_own_contracts')) {
            return $user->id === $contract->user_id;
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
        return $user->can('create_contracts');
    }

    /**
     * Determine whether the user can update the contract.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Contract  $contract
     * @return mixed
     */
    public function update(User $user, Contract $contract)
    {
        if ($user->can('update_contracts')) {
            return true;
        }

        if ($user->can('update_own_contracts')) {
            return $user->id === $contract->user_id;
        }
    }

    /**
     * Determine whether the user can update the contract state.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Contract  $contract
     * @return mixed
     */
    public function state(User $user, Contract $contract)
    {
        if ($contract->isSubmitted()) {
            return $this->deny(CTSU_01);
        }

        return $this->update($user, $contract);
    }

    /**
     * Determine whether the user can update the contract.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Contract  $contract
     * @return mixed
     */
    public function submit(User $user, Contract $contract)
    {
        if (!$this->update($user, $contract)) {
            return false;
        }

        if ($contract->query()->submitted()->activated()
            ->where('id', '!=', $contract->id)
            ->rfq($contract->customer->rfq)
            ->doesntExist()
        ) {
            return true;
        }

        return $this->deny(CTSE_01);
    }

    /**
     * Determine whether the user can delete the contract.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Quote\Contract  $contract
     * @return mixed
     */
    public function delete(User $user, Contract $contract)
    {
        if ($user->can('delete_contracts')) {
            return true;
        }

        if ($user->can('delete_own_contracts')) {
            return $user->id === $contract->user_id;
        }
    }
}
