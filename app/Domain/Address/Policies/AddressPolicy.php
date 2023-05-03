<?php

namespace App\Domain\Address\Policies;

use App\Domain\Address\Models\Address;
use App\Domain\User\Models\{User};
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any addresses.
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

        if ($user->canAny(['view_addresses', 'view_companies', 'view_opportunities'])) {
            return true;
        }
    }

    /**
     * Determine whether the user can view entities of any owner.
     *
     * @param \App\Domain\User\Models\User $user
     *
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
     * @param \App\Domain\User\Models\User $user
     *
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
     * @param \App\Domain\User\Models\User $user
     *
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
     * @param \App\Domain\User\Models\User $user
     *
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
     * @param \App\Domain\User\Models\User $user
     *
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
