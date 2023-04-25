<?php

namespace App\Domain\Company\Policies;

use App\Domain\Authorization\Enum\AccessEntityDirection;
use App\Domain\Company\Models\Company;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\ModelHasSharingUsers;
use App\Domain\User\Models\User;
use App\Foundation\Auth\Access\Response\ResponseBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->canAny(['view_companies', 'view_companies_where_editor'])) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view all models.
     */
    public function viewAll(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_companies') && $user->role->access->accessCompanyDirection === AccessEntityDirection::All) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view models related to the assigned units.
     */
    public function viewCurrentUnitsEntities(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->role->access->accessCompanyDirection !== AccessEntityDirection::Owned) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view models where editor rights are granted.
     */
    public function viewEntitiesWhereEditor(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('view_companies_where_editor')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can view entities of any owner.
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
     */
    public function view(User $user, Company $company): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        // user has direct access to the company
        if ($user->can("companies.*.{$company->getKey()}")) {
            return $this->allow();
        }

        if ($user->cant('view_companies') && $user->cant('view_companies_where_editor') && $user->cant("companies.*.{$company->getKey()}")) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('company')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $company->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('view')
                ->item('company')
                ->reason('You don\'t have an access to the unit')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($company->owner()->is($user) || $this->userInSharingUsers($company, $user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('view')
            ->item('company')
            ->reason('You must be either an owner or editor')
            ->toResponse();
    }

    /**
     * Determine whether the user can create companies.
     */
    public function create(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        if ($user->can('create_companies')) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user, Company $company): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        // user has direct access to the company
        if ($user->can("companies.*.{$company->getKey()}")) {
            return $this->allow();
        }

        if ($user->cant('update_companies') && $user->cant('update_companies_where_editor')) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('company')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $company->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('update')
                ->item('company')
                ->reason('You don\'t have an access to the unit')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($company->owner()->is($user) || $this->userInSharingUsers($company, $user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('update')
            ->item('company')
            ->reason('You must be either an owner or editor')
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

        if ($user->cant('change_companies_ownership')) {
            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('company')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $company->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('company')
                ->reason('You don\'t have an access to the unit')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($company->owner()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('change ownership')
            ->item('company')
            ->reason('You must be either an owner')
            ->toResponse();
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user, Company $company): Response
    {
        if ($company->getFlag(Company::SYSTEM)) {
            return $this->deny(CPSD_01);
        }

        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        // user has direct access to the company
        if ($user->can("companies.*.{$company->getKey()}")) {
            return $this->allow();
        }

        if ($user->cant('delete_companies')) {
            return ResponseBuilder::deny()
                ->action('delete')
                ->item('company')
                ->toResponse();
        }

        if (!$this->userHasAccessToUnit($user, $company->salesUnit)) {
            return ResponseBuilder::deny()
                ->action('change ownership')
                ->item('company')
                ->reason('You don\'t have an access to the unit')
                ->toResponse();
        }

        if ($this->userHasAccessToCurrentOrAllUnits($user)) {
            return $this->allow();
        }

        if ($company->owner()->is($user)) {
            return $this->allow();
        }

        return ResponseBuilder::deny()
            ->action('delete')
            ->item('company')
            ->reason('You must be an owner')
            ->toResponse();
    }

    private function userHasAccessToCurrentOrAllUnits(User $user): bool
    {
        return in_array($user->role->access->accessCompanyDirection, [AccessEntityDirection::CurrentUnits, AccessEntityDirection::All], true);
    }

    private function userHasAccessToUnit(User $user, ?SalesUnit $unit): bool
    {
        if ($user->role->access->accessCompanyDirection === AccessEntityDirection::All) {
            return true;
        }

        if (!$unit) {
            return false;
        }

        if ($user->salesUnitsFromLedTeams->contains($unit)) {
            return true;
        }

        if ($user->salesUnits->contains($unit)) {
            return true;
        }

        return false;
    }
}
