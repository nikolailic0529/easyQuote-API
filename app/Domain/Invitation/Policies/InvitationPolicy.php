<?php

namespace App\Domain\Invitation\Policies;

use App\Domain\Invitation\Models\Invitation;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvitationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any invitations.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        return $user->can('view_invitations');
    }

    /**
     * Determine whether the user can view the invitation.
     *
     * @return mixed
     */
    public function view(User $user, Invitation $invitation)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        return $user->can('view_invitations');
    }

    /**
     * Determine whether the user can create invitations.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        return $user->can('create_invitations');
    }

    /**
     * Determine whether the user can update the invitation.
     *
     * @return mixed
     */
    public function update(User $user, Invitation $invitation)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('update_invitations') &&
            $user->getKey() === $invitation->{$invitation->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the invitation.
     *
     * @return mixed
     */
    public function delete(User $user, Invitation $invitation)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('delete_invitations') &&
            $user->getKey() === $invitation->{$invitation->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }
}
