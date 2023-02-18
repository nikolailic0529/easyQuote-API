<?php

namespace App\Domain\Rescue\Policies;

use App\Domain\Rescue\Models\Customer;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any customers.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the customer.
     *
     * @return mixed
     */
    public function view(User $user, Customer $customer)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the customer.
     *
     * @return mixed
     */
    public function update(User $user, Customer $customer)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_quotes') && $customer->user_id === $user->getKey()) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the customer.
     *
     * @return mixed
     */
    public function delete(User $user, Customer $customer)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('delete_rfq')) {
            return true;
        }
    }
}
