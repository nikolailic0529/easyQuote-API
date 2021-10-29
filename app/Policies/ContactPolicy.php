<?php

namespace App\Policies;

use App\Models\{Contact, User};
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contacts.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->canAny(['view_contacts', 'view_opportunities'])) {
            return true;
        }
    }

    /**
     * Determine whether the user can view entities of any owner.
     *
     * @param User $user
     * @return mixed
     */
    public function viewAnyOwnerEntities(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the contact.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Contact $contact
     * @return mixed
     */
    public function view(User $user, Contact $contact)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->canAny(['view_contacts', 'view_companies', 'view_opportunities']) && $user->getKey() === $contact->{$contact->user()->getForeignKeyName()}) {
            return true;
        }
    }

    /**
     * Determine whether the user can create contacts.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->canAny(['create_contacts', 'update_companies', 'update_opportunities'])) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the contact.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Contact $contact
     * @return mixed
     */
    public function update(User $user, Contact $contact)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->canAny(['update_contacts', 'update_companies', 'update_opportunities']) && $user->getKey() === $contact->{$contact->user()->getForeignKeyName()}) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the contact.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Contact $contact
     * @return mixed
     */
    public function delete(User $user, Contact $contact)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->canAny(['delete_contacts', 'update_companies', 'update_opportunities']) && $user->getKey() === $contact->{$contact->user()->getForeignKeyName()}) {
            return true;
        }
    }
}
