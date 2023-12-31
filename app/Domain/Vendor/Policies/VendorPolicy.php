<?php

namespace App\Domain\Vendor\Policies;

use App\Domain\User\Models\{User};
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;

class VendorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any vendors.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->can('view_vendors')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view a list of entities.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return mixed
     */
    public function viewList(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_vendors')) {
            return true;
        }

        if ($user->can('create_quotes')) {
            return true;
        }

        if ($user->can('create_ww_quotes')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the vendor.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return mixed
     */
    public function view(User $user, Vendor $vendor)
    {
        if ($user->can('view_vendors')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create vendors.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->can('create_vendors')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the vendor.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return mixed
     */
    public function update(User $user, Vendor $vendor)
    {
        if ($vendor->isSystem()) {
            return $this->deny(VSU_01);
        }

        if ($user->can('update_vendors')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the vendor.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return mixed
     */
    public function delete(User $user, Vendor $vendor)
    {
        if ($vendor->isSystem()) {
            return $this->deny(VSD_01);
        }

        if ($user->can('delete_vendors')) {
            return true;
        }
    }
}
