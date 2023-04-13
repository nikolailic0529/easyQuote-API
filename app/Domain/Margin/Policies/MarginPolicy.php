<?php

namespace App\Domain\Margin\Policies;

use App\Domain\Margin\Models\CountryMargin;
use App\Domain\User\Models\User;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class MarginPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any margins.
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_margins')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view the margin.
     */
    public function view(User $user, CountryMargin $margin): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_margins')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can create margins.
     */
    public function create(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('create_margins')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can update the margin.
     */
    public function update(User $user, CountryMargin $margin): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('update_margins')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('margin')
                ->toResponse();
        }

        if ($margin->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('margin')
            ->reason('You must be an owner')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the margin.
     */
    public function delete(User $user, CountryMargin $margin): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->cant('delete_margins')) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('margin')
                ->toResponse();
        }

        if ($margin->user()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('delete')
            ->item('margin')
            ->reason('You must be an owner')
            ->toResponse();
    }
}
