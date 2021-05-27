<?php

namespace App\Policies;

use App\Models\OpportunityForm\OpportunityForm;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OpportunityFormPolicy
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
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_opportunity_forms')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\OpportunityForm\OpportunityForm $opportunityForm
     * @return mixed
     */
    public function view(User $user, OpportunityForm $opportunityForm)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_opportunity_forms')) {
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
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_opportunity_forms')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\OpportunityForm\OpportunityForm $opportunityForm
     * @return mixed
     */
    public function update(User $user, OpportunityForm $opportunityForm)
    {
        $hasPermissionsToUpdate = value(function () use ($user, $opportunityForm): bool {
            if ($user->hasRole(R_SUPER)) {
                return true;
            }

            if ($user->can('update_opportunity_forms')) {
                return true;
            }

            return false;
        });

        if (false === $hasPermissionsToUpdate) {
            return false;
        }

        if ($opportunityForm->is_system) {
            return $this->deny('You can not update a system defined Opportunity Form entity.');
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\OpportunityForm\OpportunityForm $opportunityForm
     * @return mixed
     */
    public function delete(User $user, OpportunityForm $opportunityForm)
    {
        $hasPermissionsToDelete = value(function () use ($user, $opportunityForm): bool {
            if ($user->hasRole(R_SUPER)) {
                return true;
            }

            if ($user->can('delete_opportunity_forms')) {
                return true;
            }

            return false;
        });

        if (false === $hasPermissionsToDelete) {
            return false;
        }

        if ($opportunityForm->is_system) {
            return $this->deny('You can not delete a system defined Opportunity Form entity.');
        }

        return true;
    }
}
