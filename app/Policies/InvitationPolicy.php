<?php

namespace App\Policies;

use App\Models\{
    User,
    Collaboration\Invitation
};
use Illuminate\Auth\Access\HandlesAuthorization;

class InvitationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any invitations.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('view_invitations');
    }

    /**
     * Determine whether the user can view the invitation.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Collaboration\Invitation  $invitation
     * @return mixed
     */
    public function view(User $user, Invitation $invitation)
    {
        return $user->can('view_invitations');
    }

    /**
     * Determine whether the user can create invitations.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('create_invitations');
    }

    /**
     * Determine whether the user can update the invitation.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Collaboration\Invitation  $invitation
     * @return mixed
     */
    public function update(User $user, Invitation $invitation)
    {
        return $user->can('update_invitations');
    }

    /**
     * Determine whether the user can delete the invitation.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Collaboration\Invitation  $invitation
     * @return mixed
     */
    public function delete(User $user, Invitation $invitation)
    {
        return $user->can('delete_invitations');
    }
}
