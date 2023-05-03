<?php

namespace App\Domain\Contact\Policies;

use App\Domain\Authorization\Enum\AccessEntityDirection;
use App\Domain\Contact\Models\Contact;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\{User};
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ContactPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contacts.
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_contacts')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view all models.
     */
    public function viewAll(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_contacts') && $user->role->access->accessContactDirection === AccessEntityDirection::All) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view models related to the assigned units.
     */
    public function viewCurrentUnitsEntities(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->role->access->accessContactDirection !== AccessEntityDirection::Owned) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view entities of any owner.
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
     */
    public function view(User $user, Contact $contact): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('view_contacts')) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('contact')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $contact->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('contact')
                ->reason('You don\'t have an access to the unit')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($contact->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('contact')
            ->reason('You must be an owner')
            ->toResponse();
    }

    /**
     * Determine whether the user can create contacts.
     */
    public function create(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('create_contacts')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can update the contact.
     */
    public function update(User $user, Contact $contact): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('update_contacts')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('contact')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $contact->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('contact')
                ->reason('You don\'t have an access to the unit')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($contact->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('contact')
            ->reason('You must be an owner')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the contact.
     */
    public function delete(User $user, Contact $contact): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('delete_contacts')) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('contact')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $contact->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('contact')
                ->reason('You don\'t have an access to the unit')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($contact->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('delete')
            ->item('contact')
            ->reason('You must be an owner')
            ->toResponse();
    }

    private function userHasAccessToCurrentOrAllUnits(User $user): bool
    {
        return in_array($user->role->access->accessContactDirection, [AccessEntityDirection::CurrentUnits, AccessEntityDirection::All], true);
    }

    private function userHasAccessToUnit(User $user, ?SalesUnit $unit): bool
    {
        if ($user->role->access->accessContactDirection === AccessEntityDirection::All) {
            return true;
        }

        if (!$unit) {
            return false;
        }

        if ($user->salesUnitsFromLedTeams->contains($unit)) {
            return true;
        }

        if ($user->salesUnits->contains($unit)) {
            return true;
        }

        return false;
    }
}
