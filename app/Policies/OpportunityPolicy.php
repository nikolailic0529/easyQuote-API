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
     * Determine whether the user can view the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Opportunity $opportunity
     * @return mixed
     */
    public function view(User $user, Opportunity $opportunity)
    {
        if ($user->can('view_opportunities')) {
            return true;
        }
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

        if ($user->can('update_own_opportunities') && $user->getKey() === $opportunity->{$opportunity->user()->getForeignKeyName()}) {
            return true;
        }
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
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('delete_own_opportunities') && $user->getKey() === $opportunity->{$opportunity->user()->getForeignKeyName()}) {
            return true;
        }
    }
}
