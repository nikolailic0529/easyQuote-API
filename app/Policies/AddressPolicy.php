<?php

namespace App\Policies;

use App\Models\{Address, User};
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any addresses.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->canAny(['view_addresses', 'view_companies', 'view_opportunities'])) {
            return true;
        }
    }

    /**
     * Determine whether the user can view entities of any owner.
     *
     * @param User $user
     * @return mixed
     */
    public function viewAnyOwnerEntities(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the address.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Address $address
     * @return mixed
     */
    public function view(User $user, Address $address)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->canAny(['view_addresses', 'view_companies', 'view_opportunities'])) {
            return true;
        }
    }

    /**
     * Determine whether the user can create addresses.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->canAny(['create_addresses', 'update_companies', 'update_opportunities'])) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the address.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Address $address
     * @return mixed
     */
    public function update(User $user, Address $address)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (!$user->canAny(['update_addresses', 'update_companies', 'update_opportunities'])) {
            return false;
        }

        if ($user->getKey() !== $address->{$address->user()->getForeignKeyName()}) {
            return $this->deny("You can't update the address owned by another user.");
        }

        return true;
    }

    /**
     * Determine whether the user can delete the address.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Address $address
     * @return mixed
     */
    public function delete(User $user, Address $address)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (!$user->canAny(['delete_addresses', 'update_companies', 'update_opportunities'])) {
            return false;
        }

        if ($user->getKey() !== $address->{$address->user()->getForeignKeyName()}) {
            return $this->deny("You can't delete the address owned by another user.");
        }

        return true;
    }
}
