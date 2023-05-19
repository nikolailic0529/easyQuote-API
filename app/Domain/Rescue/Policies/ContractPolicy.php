<?php

namespace App\Domain\Rescue\Policies;

use App\Domain\Rescue\Models\Contract;
use App\Domain\User\Models\User;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ContractPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contracts.
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->canAny(['view_contracts', 'view_own_contracts'])) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('contracts')
            ->toResponse();
    }

    /**
     * Determine whether the user can view the contract.
     */
    public function view(User $user, Contract $contract): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->canAny(['view_contracts', 'view_own_contracts'])) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('contract')
            ->toResponse();
    }

    /**
     * Determine whether the user can create contracts.
     */
    public function create(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('create_contracts')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('create')
            ->item('contract')
            ->toResponse();
    }

    /**
     * Determine whether the user can update the contract.
     */
    public function update(User $user, Contract $contract): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can("contracts.update.{$contract->getKey()}")) {
            return $this->allow();
        }

        if ($user->can("contracts.update.user.{$contract->user()->getParentKey()}")) {
            return $this->allow();
        }

        if ($user->cant('update_own_contracts')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('contract')
                ->toResponse();
        }

        if ($contract->user()->isNot($user)) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('contract')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can update the contract state.
     */
    public function state(User $user, Contract $contract): Response
    {
        $updateResponse = $this->update($user, $contract);

        if ($updateResponse->denied()) {
            return $updateResponse;
        }

        if ($contract->isSubmitted()) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('contract')
                ->reason('Contract is submitted')
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can update the contract.
     */
    public function submit(User $user, Contract $contract): Response
    {
        $updateResponse = $this->update($user, $contract);

        if ($updateResponse->denied()) {
            return $updateResponse;
        }

        $activeSubmittedContractExists = $contract->newQuery()
            ->whereNotNull('submitted_at')
            ->whereNotNull('activated_at')
            ->whereKeyNot($contract->getKey())
            ->whereRelation('customer', 'rfq', '=', $contract->customer->rfq)
            ->exists();

        if (!$activeSubmittedContractExists) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('submit')
            ->item('contract')
            ->reason('An active submitted contract for the same number already exists')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the contract.
     */
    public function delete(User $user, Contract $contract): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can("contracts.delete.user.{$contract->user_id}")) {
            return $this->allow();
        }

        if ($user->cant('delete_own_contracts')) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('contract')
                ->toResponse();
        }

        if ($contract->user()->isNot($user)) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('contract')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return $this->allow();
    }
}
