<?php

namespace App\Domain\Rescue\Policies;

use App\Domain\Rescue\Models\Customer;
use App\Domain\User\Models\User;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_own_quotes')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view the customer.
     */
    public function view(User $user, Customer $customer): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_own_quotes')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view the customer.
     */
    public function update(User $user, Customer $customer): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('create_quotes')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('customer')
                ->toResponse();
        }

        if ($customer->owner()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('customer')
            ->reason('You must be an owner')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the customer.
     */
    public function delete(User $user, Customer $customer): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('delete_rfq')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('delete')
            ->item('customer')
            ->toResponse();
    }
}
