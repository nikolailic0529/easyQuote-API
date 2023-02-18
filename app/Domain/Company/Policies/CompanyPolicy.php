<?php

namespace App\Domain\Company\Policies;

use App\Domain\Company\Models\Company;
use App\Domain\User\Models\{ModelHasSharingUsers, User};
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any companies.
     *
     * @param \App\Domain\User\Models\User $user
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
     * @param \App\Domain\User\Models\User $user
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
     * @param \App\Domain\User\Models\User $user
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
     * @param \App\Domain\User\Models\User $user
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
     * @param \App\Domain\User\Models\User $user
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
            if ($company->owner()->is($user) || $this->userInSharingUsers($company, $user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('update')
                ->item('company')
                ->reason('You must be either an owner or editor')
                ->toResponse();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('company')
            ->toResponse();
    }

    protected function userInSharingUsers(Company $company, User $user): bool
    {
        $userForeignKey = \once(static function (): string {
            return (new ModelHasSharingUsers())->user()->getForeignKeyName();
        });

        return $company->sharingUserRelations
            ->lazy()
            ->pluck($userForeignKey)
            ->containsStrict($user->getKey());
    }

    /**
     * Determine whether the user can update the company.
     */
    public function changeOwnership(User $user, Company $company): Response
    {
        if ($company->getFlag(Company::SYSTEM)) {
            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('company')
                ->reason('System defined company')
                ->toResponse();
        }

        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if (!$user->hasPermissionTo('change_companies_ownership')) {
            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('company')
                ->toResponse();
        }

        if ($user->salesUnitsFromLedTeams->contains($company->salesUnit)) {
            return $this->allow();
        }

        if ($user->salesUnits->contains($company->salesUnit)) {
            if ($company->owner()->is($user) || $this->userInSharingUsers($company, $user)) {
                return $this->allow();
            }

            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('company')
                ->reason('You must be either an owner or editor')
                ->toResponse();
        }

        return ResponseBuilder::deny()
            ->action('change ownership')
            ->item('company')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the company.
     *
     * @param \App\Domain\User\Models\User $user
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
            if ($company->owner()->is($user) || $this->userInSharingUsers($company, $user)) {
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
