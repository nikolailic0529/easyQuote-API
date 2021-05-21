<?php

namespace App\Policies;

use App\Models\Pipeline\Pipeline;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PipelinePolicy
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

        if ($user->canAny(['view_pipelines', 'view_opportunities'])) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Pipeline\Pipeline $pipeline
     * @return mixed
     */
    public function view(User $user, Pipeline $pipeline)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->canAny(['view_pipelines', 'view_opportunities'])) {
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

        if ($user->can('create_pipelines')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Pipeline\Pipeline $pipeline
     * @return mixed
     */
    public function update(User $user, Pipeline $pipeline)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('update_pipelines')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Pipeline\Pipeline $pipeline
     * @return mixed
     */
    public function delete(User $user, Pipeline $pipeline)
    {
        if ($pipeline->is_system) {
            return $this->deny('You can not delete a system defined Pipeline entity.');
        }

        if ($pipeline->is_default) {
            return $this->deny('You can not delete a default Pipeline entity.');
        }

        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('delete_pipelines')) {
            return true;
        }
    }
}
