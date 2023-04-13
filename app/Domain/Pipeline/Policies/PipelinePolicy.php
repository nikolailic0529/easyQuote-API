<?php

namespace App\Domain\Pipeline\Policies;

use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PipelinePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $this->allow();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pipeline $pipeline): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): mixed
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pipeline $pipeline): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pipeline $pipeline): Response
    {
        if ($pipeline->is_system) {
            return $this->deny('You can not delete a system defined Pipeline entity.');
        }

        if ($pipeline->is_default) {
            return $this->deny('You can not delete a default Pipeline entity.');
        }

        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }
}
