<?php

namespace App\Domain\Asset\Policies;

use App\Domain\Asset\Models\Asset;
use App\Domain\User\Models\User;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssetPolicy
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

        if ($user->can('view_assets')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view any')
            ->item('asset')
            ->toResponse();
    }

    /**
     * Determine whether the user can view models of any owner.
     */
    public function viewAnyOwnerEntities(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Asset $asset): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_assets')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('asset')
            ->toResponse();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('create_assets')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('create')
            ->item('asset')
            ->toResponse();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Asset $asset): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('update_assets')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('asset')
            ->toResponse();
    }

    public function changeOwnership(User $user, Asset $asset): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->hasPermissionTo('change_assets_ownership')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('change ownership of')
            ->item('assets')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Asset $asset): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('delete_assets')) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('delete')
            ->item('asset')
            ->toResponse();
    }
}
