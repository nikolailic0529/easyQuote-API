<?php

namespace App\Policies;

use App\Models\Customer\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any customers.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the customer.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Customer\Customer  $customer
     * @return mixed
     */
    public function view(User $user, Customer $customer)
    {
        return $user->can('create_quotes');
    }

    /**
     * Determine whether the user can view the customer.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Customer\Customer  $customer
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
     * @param  \App\Models\User  $user
     * @param  \App\Models\Customer\Customer  $customer
     * @return mixed
     */
    public function delete(User $user, Customer $customer)
    {
        return $user->can('delete_customers');
    }
}
