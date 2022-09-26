<?php

namespace App\Policies;

use App\Models\{Contact, User};
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ContactPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contacts.
     *
     * @param  \App\Models\User  $user
     * @return Response
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->canAny(['view_contacts', 'view_companies', 'view_opportunities'])) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view entities of any owner.
     *
     * @param  User  $user
     * @return Response
     */
    public function viewAnyOwnerEntities(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view the contact.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Contact  $contact
     * @return Response
     */
    public function view(User $user, Contact $contact): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->canAny(['view_contacts', 'view_companies', 'view_opportunities'])) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can create contacts.
     *
     * @param  \App\Models\User  $user
     * @return Response
     */
    public function create(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->canAny(['create_contacts', 'update_companies', 'update_opportunities'])) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can update the contact.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Contact  $contact
     * @return Response
     */
    public function update(User $user, Contact $contact): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if (!$user->canAny(['update_contacts', 'update_companies', 'update_opportunities'])) {
            return $this->deny();
        }

        if ($user->salesUnitsFromLedTeams->contains($contact->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($contact->salesUnit)) {
            if ($contact->user()->is($user)) {
                return $this->allow();
            }

            return $this->deny();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can delete the contact.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Contact  $contact
     * @return Response
     */
    public function delete(User $user, Contact $contact): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if (!$user->canAny(['delete_contacts', 'update_companies', 'update_opportunities'])) {
            return $this->deny();
        }

        if ($user->salesUnitsFromLedTeams->contains($contact->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($contact->salesUnit)) {
            if ($contact->user()->is($user)) {
                return $this->allow();
            }

            return $this->deny();
        }

        return $this->deny();
    }
}
