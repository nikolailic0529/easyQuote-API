<?php

namespace App\Policies;

use App\Models\{
    User,
    Company
};
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any companies.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->can('view_companies')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view entities of any owner.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function viewAnyOwnerEntities(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the company.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Company  $company
     * @return mixed
     */
    public function view(User $user, Company $company)
    {
        if ($user->canAny(['view_companies', 'view_opportunities', "companies.*.{$company->getKey()}"])) {
            return true;
        }
    }

    /**
     * Determine whether the user can create companies.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->can('create_companies')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the company.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Company  $company
     * @return mixed
     */
    public function update(User $user, Company $company)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can("companies.*.{$company->getKey()}")) {
            return true;
        }

        if ($user->canAny('update_companies')) {

            if ($user->getKey() !== $company->{$company->user()->getForeignKeyName()}) {
                return $this->deny("You can't update the company owned by another user.");
            }

            return true;
        }
    }

    /**
     * Determine whether the user can delete the company.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Company  $company
     * @return mixed
     */
    public function delete(User $user, Company $company)
    {
        if ($company->getFlag(Company::SYSTEM)) {
            return $this->deny(CPSD_01);
        }

        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can("companies.*.{$company->getKey()}")) {
            return true;
        }

        if ($user->canAny('delete_companies')) {

            if ($user->getKey() !== $company->{$company->user()->getForeignKeyName()}) {
                return $this->deny("You can't delete the company owned by another user.");
            }

            return true;
        }
    }
}
