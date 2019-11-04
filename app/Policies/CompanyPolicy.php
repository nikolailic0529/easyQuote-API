<?php namespace App\Policies;

use App\Models \ {
    User,
    Company
};
use Illuminate\Auth\Access \ {
    HandlesAuthorization,
    Response
};

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
        if($user->can('view_companies')) {
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
        if($user->can('view_companies')) {
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
        if($user->can('create_companies')) {
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
        if($company->isSystem()) {
            return Response::deny(__('company.system_updating_exception'));
        }

        if($user->can('update_companies')) {
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
        if($company->isSystem()) {
            return Response::deny(__('company.system_deleting_exception'));
        }

        if($user->can('delete_companies')) {
            return true;
        }
    }
}
