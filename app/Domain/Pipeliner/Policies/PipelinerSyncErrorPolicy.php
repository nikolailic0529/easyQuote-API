<?php

namespace App\Domain\Pipeliner\Policies;

use App\Domain\Pipeliner\Models\PipelinerSyncError;
use App\Domain\User\Models\User;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PipelinerSyncErrorPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
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
     * Determine whether the user can archive the model.
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

        if (null !== $pipelinerSyncError->archived_at) {
            return ResponseBuilder::deny()
                ->action('restore from archive')
                ->item('sync error')
                ->reason(__('The error has already been archived.'))
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can archive any models.
     */
    public function archiveAny(User $user): Response
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
     * Determine whether the user can restore the model from archive.
     */
    public function restoreFromArchive(User $user, PipelinerSyncError $pipelinerSyncError): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->salesUnits->isEmpty()) {
            return ResponseBuilder::deny()
                ->action('restore from archive')
                ->item('sync error')
                ->reason(__("You don't have access to any sales unit."))
                ->toResponse();
        }

        if (null === $pipelinerSyncError->archived_at) {
            return ResponseBuilder::deny()
                ->action('restore from archive')
                ->item('sync error')
                ->reason(__('The error is not archived.'))
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can restore any models from archive.
     */
    public function restoreAnyFromArchive(User $user): Response
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
