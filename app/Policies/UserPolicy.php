<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Http\Requests\Collaboration\UpdateUserRequest;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
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
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $collaborator
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
     * @param  \App\Models\User  $user
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
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $collaborator
     * @param  \App\Http\Requests\Collaboration\UpdateUserRequest $request
     * @return mixed
     */
    public function updateProfile(User $user, User $collaborator, UpdateUserRequest $request)
    {
        if ($user->cant('update_users')) {
            return false;
        }

        /**
         * When Updatable Collaborator doesn't have Administrator role we allow the action.
         */
        if (!$collaborator->hasRole('Administrator')) {
            return true;
        }

        /**
         * When Updatable Collaborator has Administrator role and User is trying to update his email we deny the action.
         */
        if ($request->has('email') && $request->email !== $collaborator->email) {
            return $this->deny(AEU_01);
        }

        return true;
    }

    /**
     * Determine whether the user can update the collaborator.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $collaborator
     * @param  \App\Http\Requests\Collaboration\UpdateUserRequest $request
     * @return mixed
     */
    public function update(User $user, User $collaborator)
    {
        return $user->can('update_users');
    }

    /**
     * Determine whether the user can delete the collaborator.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $collaborator
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
