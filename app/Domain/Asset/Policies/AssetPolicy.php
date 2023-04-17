<?php

namespace App\Domain\Asset\Policies;

use App\Domain\Asset\Models\Asset;
use App\Domain\User\Models\ModelHasSharingUsers;
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
     * Determine whether the user can view all models.
     */
    public function viewAll(User $user): Response
    {
        return $user->hasRole(R_SUPER)
            ? $this->allow()
            : $this->deny();
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

        if ($user->cant('view_assets')) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('asset')
                ->toResponse();
        }

        if ($asset->user()->is($user) || $this->userInSharingUsers($asset, $user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('asset')
            ->reason('You must be either an owner or editor')
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
     */
    public function update(User $user, Asset $asset): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('update_assets')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('asset')
                ->toResponse();
        }

        if ($asset->user()->is($user) || $this->userInSharingUsers($asset, $user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('asset')
            ->reason('You must be either an owner or editor')
            ->toResponse();
    }

    public function changeOwnership(User $user, Asset $asset): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('change_assets_ownership')) {
            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('asset')
                ->toResponse();
        }

        if ($asset->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('change ownership')
            ->item('asset')
            ->reason('You must be an owner')
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

        if ($user->cant('delete_assets')) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('asset')
                ->toResponse();
        }

        if ($asset->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('delete')
            ->item('asset')
            ->reason('You must be an owner')
            ->toResponse();
    }

    protected function userInSharingUsers(Asset $asset, User $user): bool
    {
        $userForeignKey = \once(static function (): string {
            return (new ModelHasSharingUsers())->user()->getForeignKeyName();
        });

        return $asset->sharingUserRelations
            ->lazy()
            ->pluck($userForeignKey)
            ->containsStrict($user->getKey());
    }
}
