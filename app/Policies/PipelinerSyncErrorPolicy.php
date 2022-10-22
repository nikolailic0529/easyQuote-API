<?php

namespace App\Policies;

use App\Models\Pipeliner\PipelinerSyncError;
use App\Models\User;
use App\Policies\Access\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PipelinerSyncErrorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return Response
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->salesUnits->isEmpty()) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('any sync errors')
                ->reason(__("You don't have access to any sales unit."))
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pipeliner\PipelinerSyncError  $pipelinerSyncError
     * @return Response
     */
    public function view(User $user, PipelinerSyncError $pipelinerSyncError): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->salesUnits->isEmpty()) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('sync error')
                ->reason(__("You don't have access to any sales unit."))
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pipeliner\PipelinerSyncError  $pipelinerSyncError
     * @return Response
     */
    public function archive(User $user, PipelinerSyncError $pipelinerSyncError): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->salesUnits->isEmpty()) {
            return ResponseBuilder::deny()
                ->action('archive')
                ->item('sync error')
                ->reason(__("You don't have access to any sales unit."))
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pipeliner\PipelinerSyncError  $pipelinerSyncError
     * @return Response
     */
    public function delete(User $user, PipelinerSyncError $pipelinerSyncError): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->salesUnits->isEmpty()) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('sync errors')
                ->reason(__("You don't have access to any sales unit."))
                ->toResponse();
        }

        return $this->allow();
    }
}
