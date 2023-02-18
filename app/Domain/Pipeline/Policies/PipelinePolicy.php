<?php

namespace App\Domain\Pipeline\Policies;

use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PipelinePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Pipeline $pipeline)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Pipeline $pipeline)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
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
    }
}
