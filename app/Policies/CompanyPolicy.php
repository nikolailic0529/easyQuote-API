<?php

namespace App\Policies;

use App\Policies\Access\ResponseBuilder;
use App\Models\{Company, User};
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any companies.
     *
     * @param  \App\Models\User  $user
     * @return Response
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_companies')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view entities of any owner.
     *
     * @param  \App\Models\User  $user
     * @return Response
     */
    public function viewAnyOwnerEntities(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view the company.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Company  $company
     * @return Response
     */
    public function view(User $user, Company $company): Response
    {
        if ($user->canAny(['view_companies', 'view_opportunities', "companies.*.{$company->getKey()}"])) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('company')
            ->toResponse();
    }

    /**
     * Determine whether the user can create companies.
     *
     * @param  User  $user
     * @return Response
     */
    public function create(User $user): Response
    {
        if ($user->can('create_companies')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can update the company.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Company  $company
     * @return Response
     */
    public function update(User $user, Company $company): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can("companies.*.{$company->getKey()}")) {
            return $this->allow();
        }

        if ($user->cant('update_companies')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('company')
                ->toResponse();
        }

        if ($user->salesUnitsFromLedTeams->contains($company->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($company->salesUnit)) {
            if ($company->owner()->is($user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('update')
                ->item('company')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('company')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the company.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Company  $company
     * @return Response
     */
    public function delete(User $user, Company $company): Response
    {
        if ($company->getFlag(Company::SYSTEM)) {
            return $this->deny(CPSD_01);
        }

        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can("companies.*.{$company->getKey()}")) {
            return $this->allow();
        }

        if ($user->cant('delete_companies')) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('company')
                ->toResponse();
        }

        if ($user->salesUnitsFromLedTeams->contains($company->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($company->salesUnit)) {
            if ($company->owner()->is($user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('delete')
                ->item('company')
                ->reason('You must be an owner')
                ->toResponse();
        }

        return ResponseBuilder::deny()
            ->action('delete')
            ->item('company')
            ->toResponse();
    }
}
