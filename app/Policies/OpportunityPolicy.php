<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OpportunityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->can('view_opportunities')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view models of any owner.
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
     * Determine whether the user can view the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Opportunity $opportunity
     * @return mixed
     */
    public function view(User $user, Opportunity $opportunity)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->getKey() === $opportunity->primaryAccount()->getParentKey() && $user->cant('view_opportunities')) {
            return $this->deny("You are an owner of the Opportunity, but you don't have permissions to view it. Contact with your Account Manager.");
        }

        if ($user->getKey() === $opportunity->user()->getParentKey() && $user->cant('view_opportunity')) {
            return $this->deny("You are a creator of the Opportunity, but you don't have permissions to view it. Contact with your Account Manager.");
        }

        if ($user->cant('view_opportunities')) {
            return $this->deny("You do not have permissions to view any Opportunity. Contact with your Account Manager.");
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->can('create_opportunities')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Opportunity $opportunity
     * @return mixed
     */
    public function update(User $user, Opportunity $opportunity)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->getKey() === $opportunity->primaryAccount()->getParentKey() && $user->cant('update_own_opportunities')) {
            return $this->deny("You are an owner of the Opportunity, but you don't have permissions to update it. Contact with your Account Manager.");
        }

        if ($user->getKey() === $opportunity->user()->getParentKey() && $user->cant('update_own_opportunities')) {
            return $this->deny("You are a creator of the Opportunity, but you don't have permissions to update it. Contact with your Account Manager.");
        }

        if ($user->cant('update_own_opportunities')) {
            return $this->deny("You do not have permissions to update any Opportunity. Contact with your Account Manager.");
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Opportunity $opportunity
     * @return mixed
     */
    public function delete(User $user, Opportunity $opportunity)
    {
        $ensureOpportunityDoesntHaveQuotes = function (Opportunity $opportunity) {
            if ($opportunity->worldwideQuotes()->exists()) {
                return $this->deny('You can\'nt to delete the Opportunity. It\'s already attached to one or more Quotes.');
            }

            return true;
        };

        if ($user->hasRole(R_SUPER)) {
            return $ensureOpportunityDoesntHaveQuotes($opportunity);
        }

        if ($user->getKey() === $opportunity->primaryAccount()->getParentKey() && $user->cant('update_own_opportunities')) {
            return $this->deny("You are an owner of the Opportunity, but you don't have permissions to update it. Contact with your Account Manager.");
        }

        if ($user->getKey() === $opportunity->user()->getParentKey() && $user->cant('update_own_opportunities')) {
            return $this->deny("You are a creator of the Opportunity, but you don't have permissions to update it. Contact with your Account Manager.");
        }

        if ($user->cant('update_own_opportunities')) {
            return $this->deny("You do not have permissions to update any Opportunity. Contact with your Account Manager.");
        }

        return $ensureOpportunityDoesntHaveQuotes($opportunity);
    }
}
