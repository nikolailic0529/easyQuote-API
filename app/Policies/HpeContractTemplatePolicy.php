<?php

namespace App\Policies;

use App\Models\QuoteTemplate\HpeContractTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HpeContractTemplatePolicy
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
     * @param  \App\Models\QuoteTemplate\HpeContractTemplate  $hpeContractTemplate
     * @return mixed
     */
    public function view(User $user, HpeContractTemplate $hpeContractTemplate)
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
     * @param  \App\Models\QuoteTemplate\HpeContractTemplate  $hpeContractTemplate
     * @return mixed
     */
    public function update(User $user, HpeContractTemplate $hpeContractTemplate)
    {
        if ($hpeContractTemplate->isSystem()) {
            return $this->deny(QTSU_01);
        }

        return $user->can('update_templates');
    }

    /**
     * Determine whether the user can delete the contract template.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\QuoteTemplate\HpeContractTemplate  $hpeContractTemplate
     * @return mixed
     */
    public function delete(User $user, HpeContractTemplate $hpeContractTemplate)
    {
        if ($hpeContractTemplate->isSystem()) {
            return $this->deny(QTSD_01);
        }

        return $user->can('delete_templates');
    }

    /**
     * Determine whether the user can make copy of the template.
     *
     * @param \App\Models\User $user
     * @param \App\Models\QuoteTemplate\HpeContractTemplate $hpeContractTemplate
     * @return mixed
     */
    public function copy(User $user, HpeContractTemplate $hpeContractTemplate)
    {
        return $this->create($user) && $this->view($user, $hpeContractTemplate);
    }
}
