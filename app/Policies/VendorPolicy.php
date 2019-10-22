<?php namespace App\Policies;

use App\Models \ {
    User,
    Vendor
};
use Illuminate\Auth\Access\HandlesAuthorization;

class VendorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any vendors.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the vendor.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vendor  $vendor
     * @return mixed
     */
    public function view(User $user, Vendor $vendor)
    {
        return $user->collaboration_id === $vendor->collaboration_id || $vendor->isSystem();
    }

    /**
     * Determine whether the user can create vendors.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if($user->can('create_vendors')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the vendor.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vendor  $vendor
     * @return mixed
     */
    public function update(User $user, Vendor $vendor)
    {
        if($user->can('update_collaboration_vendors')) {
            return $user->collaboration_id === $vendor->collaboration_id;
        }

        if($user->can('update_own_vendors')) {
            return $user->id === $vendor->user_id;
        }
    }

    /**
     * Determine whether the user can delete the vendor.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Vendor  $vendor
     * @return mixed
     */
    public function delete(User $user, Vendor $vendor)
    {
        if($user->can('delete_collaboration_vendors')) {
            return $user->collaboration_id === $vendor->collaboration_id;
        }

        if($user->can('delete_own_vendors')) {
            return $user->id === $vendor->user_id;
        }
    }
}
