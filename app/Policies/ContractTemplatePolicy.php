<?php

namespace App\Policies;

use App\Models\Template\ContractTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any contract templates.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
        
        if ($user->can('view_own_contract_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the contract template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template\ContractTemplate  $contractTemplate
     * @return mixed
     */
    public function view(User $user, ContractTemplate $contractTemplate)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
        
        if ($user->can('view_own_contract_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create contract templates.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }
        
        if ($user->can('create_contract_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the contract template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template\ContractTemplate  $contractTemplate
     * @return mixed
     */
    public function update(User $user, ContractTemplate $contractTemplate)
    {
        if ($contractTemplate->isSystem()) {
            return $this->deny(QTSU_01);
        }

        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('update_own_contract_templates') &&
            $user->getKey() === $contractTemplate->{$contractTemplate->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the contract template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template\ContractTemplate  $contractTemplate
     * @return mixed
     */
    public function delete(User $user, ContractTemplate $contractTemplate)
    {
        if ($contractTemplate->isSystem()) {
            return $this->deny(QTSD_01);
        }

        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if (
            $user->can('delete_own_contract_templates') &&
            $user->getKey() === $contractTemplate->{$contractTemplate->user()->getForeignKeyName()}
        ) {
            return true;
        }
    }

    /**
     * Determine whether the user can make copy of the template.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Template\ContractTemplate $contractTemplate
     * @return mixed
     */
    public function copy(User $user, ContractTemplate $contractTemplate)
    {
        return $this->create($user) && $this->view($user, $contractTemplate);
    }
}
