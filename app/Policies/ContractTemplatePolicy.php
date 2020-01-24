<?php

namespace App\Policies;

use App\Models\QuoteTemplate\ContractTemplate;
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
        return $user->can('view_templates');
    }

    /**
     * Determine whether the user can view the contract template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\ContractTemplate  $contractTemplate
     * @return mixed
     */
    public function view(User $user, ContractTemplate $contractTemplate)
    {
        return $user->can('view_templates');
    }

    /**
     * Determine whether the user can create contract templates.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('create_templates');
    }

    /**
     * Determine whether the user can update the contract template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\ContractTemplate  $contractTemplate
     * @return mixed
     */
    public function update(User $user, ContractTemplate $contractTemplate)
    {
        if ($contractTemplate->isSystem()) {
            return $this->deny(QTSU_01);
        }

        return $user->can('update_templates');
    }

    /**
     * Determine whether the user can delete the contract template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\ContractTemplate  $contractTemplate
     * @return mixed
     */
    public function delete(User $user, ContractTemplate $contractTemplate)
    {
        if ($contractTemplate->isSystem()) {
            return $this->deny(QTSD_01);
        }

        return $user->can('delete_templates');
    }

    /**
     * Determine whether the user can make copy of the template.
     *
     * @param \App\Models\User $user
     * @param \App\Models\QuoteTemplate\ContractTemplate $contractTemplate
     * @return mixed
     */
    public function copy(User $user, ContractTemplate $contractTemplate)
    {
        return $this->create($user) && $this->view($user, $contractTemplate);
    }
}
