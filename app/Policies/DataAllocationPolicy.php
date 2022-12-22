<?php

namespace App\Policies;

use App\Enum\DataAllocationStageEnum;
use App\Models\DataAllocation\DataAllocation;
use App\Models\User;
use App\Policies\Access\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DataAllocationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user
     * @return Response
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_own_data_allocations')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user
     * @param  DataAllocation  $dataAllocation
     * @return Response
     */
    public function view(User $user, DataAllocation $dataAllocation): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('view_own_data_allocations')) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('data allocation')
                ->toResponse();
        }

        if ($dataAllocation->owner()->isNot($user)) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('data allocation')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user
     * @return Response
     */
    public function create(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('create_data_allocations')) {
            return ResponseBuilder::deny()
                ->action('create')
                ->item('data allocation')
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @param  DataAllocation  $dataAllocation
     * @return Response
     */
    public function update(User $user, DataAllocation $dataAllocation): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('update_own_data_allocations')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('data allocation')
                ->toResponse();
        }

        if ($dataAllocation->owner()->isNot($user)) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('data allocation')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return $this->allow();
    }

    public function processImportStage(User $user, DataAllocation $dataAllocation): Response
    {
        if (($response = $this->update($user, $dataAllocation))->denied()) {
            return $response;
        }

        return $this->allow();
    }

    public function processReviewStage(User $user, DataAllocation $dataAllocation): Response
    {
        if (($response = $this->update($user, $dataAllocation))->denied()) {
            return $response;
        }

        if ($dataAllocation->stage->value < DataAllocationStageEnum::Import->value) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('data allocation')
                ->reason('Can not process review stage before import')
                ->toResponse();
        }

        return $this->allow();
    }

    public function processResultsStage(User $user, DataAllocation $dataAllocation): Response
    {
        if (($response = $this->update($user, $dataAllocation))->denied()) {
            return $response;
        }

        if ($dataAllocation->stage->value < DataAllocationStageEnum::Review->value) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('data allocation')
                ->reason('Can not process results stage before review')
                ->toResponse();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user
     * @param  DataAllocation  $dataAllocation
     * @return Response
     */
    public function delete(User $user, DataAllocation $dataAllocation): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('delete_own_data_allocations')) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('data allocation')
                ->toResponse();
        }

        if ($dataAllocation->owner()->isNot($user)) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('data allocation')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return $this->allow();
    }
}
