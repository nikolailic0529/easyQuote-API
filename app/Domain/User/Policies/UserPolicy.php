<?php

namespace App\Domain\User\Policies;

use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->can('view_users')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the collaborator.
     *
     * @return mixed
     */
    public function view(User $user, User $collaborator)
    {
        if ($user->can('view_users')) {
            return true;
        }
    }

    /**
     * Determine whether the user can invite collaboration users.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->can('invite_collaboration_users')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update profile the collaborator.
     *
     * @return mixed
     */
    public function updateProfile(User $user, User $collaborator)
    {
        if ($user->can('update_users')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the collaborator.
     *
     * @return mixed
     */
    public function update(User $user, User $collaborator)
    {
        return $user->can('update_users');
    }

    /**
     * Determine whether the user can delete the collaborator.
     *
     * @return mixed
     */
    public function delete(User $user, User $collaborator)
    {
        if (!$user->can('delete_users')) {
            return false;
        }

        if ($user->id === $collaborator->id) {
            return $this->deny(USD_01);
        }

        return true;
    }
}
