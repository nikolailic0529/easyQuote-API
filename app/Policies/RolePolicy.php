<?php namespace App\Policies;

use App\Models \ {
    User,
    Role
};
use Illuminate\Auth\Access \ {
    HandlesAuthorization,
    Response
};

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any roles.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if($user->can('view_roles')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the role.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Role  $role
     * @return mixed
     */
    public function view(User $user, Role $role)
    {
        if($user->can('view_roles')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create roles.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if($user->can('create_roles')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the role.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Role  $role
     * @return mixed
     */
    public function update(User $user, Role $role)
    {
        if($role->isSystem()) {
            return Response::deny('role.system_updating_exception');
        }

        if($user->can('update_roles')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the role.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Role  $role
     * @return mixed
     */
    public function delete(User $user, Role $role)
    {
        if($role->isSystem()) {
            return Response::deny('role.system_deleting_exception');
        }

        if($user->can('delete_roles')) {
            return true;
        }
    }
}
