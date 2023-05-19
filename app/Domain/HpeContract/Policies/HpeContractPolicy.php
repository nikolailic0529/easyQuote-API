<?php

namespace App\Domain\HpeContract\Policies;

use App\Domain\HpeContract\Models\HpeContract;
use App\Domain\User\Models\{User};
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class HpeContractPolicy
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

        return $this->deny();
    }

    /**
     * Determine whether the user can view the contract.
     */
    public function view(User $user, HpeContract $contract): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->canAny(['view_contracts', 'view_own_contracts'])) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('hpe contract')
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
            ->item('hpe contract')
            ->toResponse();
    }

    /**
     * Determine whether the user can update the contract.
     */
    public function update(User $user, HpeContract $contract): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('update_own_contracts')) {
            return ResponseBuilder::deny()
                ->action('create')
                ->item('hpe contract')
                ->toResponse();
        }

        if ($contract->user()->isNot($user)) {
            return ResponseBuilder::deny()
                ->action('create')
                ->item('hpe contract')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can delete the contract.
     */
    public function delete(User $user, HpeContract $contract): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('delete_own_contracts')) {
            return ResponseBuilder::deny()
                ->action('create')
                ->item('hpe contract')
                ->toResponse();
        }

        if ($contract->user()->isNot($user)) {
            return ResponseBuilder::deny()
                ->action('create')
                ->item('hpe contract')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can make a new copy of the contract.
     */
    public function copy(User $user, HpeContract $contract): Response
    {
        $createResponse = $this->create($user);

        if ($createResponse->denied()) {
            return ResponseBuilder::deny()
                ->action('copy')
                ->item('hpe contract')
                ->toResponse();
        }

        return $this->allow();
    }
}
