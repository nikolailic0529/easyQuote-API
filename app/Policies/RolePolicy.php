<?php

namespace App\Policies;

use App\Models\{
    User,
    Role
};
use Illuminate\Auth\Access\HandlesAuthorization;

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
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        return $user->can('view_roles');
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
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        return $user->can('view_roles');
    }

    /**
     * Determine whether the user can create roles.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        return $user->can('create_roles');
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
        if ($role->isSystem()) {
            return $this->deny(RSU_01);
        }

        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('update_roles')) {
            if ($user->getKey() === $role->{$role->user()->getForeignKeyName()}) {
                return true;
            }

            return $this->deny('You do not have update permissions for this role.');
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
        if ($role->isSystem()) {
            return $this->deny(RSD_01);
        }

        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('delete_roles')) {
            if ($user->getKey() === $role->{$role->user()->getForeignKeyName()}) {
                return true;
            }

            return $this->deny('You do not have delete permissions for this role.');
        }
    }
}
