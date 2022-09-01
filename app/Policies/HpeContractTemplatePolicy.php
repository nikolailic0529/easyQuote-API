<?php

namespace App\Policies;

use App\Models\Template\HpeContractTemplate;
use App\Models\User;
use App\Services\Auth\UserTeamGate;
use Illuminate\Auth\Access\HandlesAuthorization;

class HpeContractTemplatePolicy
{
    use HandlesAuthorization;

    public function __construct(protected UserTeamGate $userTeamGate)
    {
    }

    /**
     * Determine whether the user can view any contract templates.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_own_hpe_contract_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the contract template.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Template\HpeContractTemplate $hpeContractTemplate
     * @return mixed
     */
    public function view(User $user, HpeContractTemplate $hpeContractTemplate)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_own_hpe_contract_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create contract templates.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_hpe_contract_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the contract template.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Template\HpeContractTemplate $hpeContractTemplate
     * @return mixed
     */
    public function update(User $user, HpeContractTemplate $hpeContractTemplate)
    {
        if ($hpeContractTemplate->isSystem()) {
            return $this->deny(QTSU_01);
        }

        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->cannot('update_own_hpe_contract_templates')) {
            return false;
        }

        if ($hpeContractTemplate->user()->is($user)) {
            return true;
        }

        if ($this->userTeamGate->isLedByUser($hpeContractTemplate->user()->getParentKey(), $user)) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the contract template.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Template\HpeContractTemplate $hpeContractTemplate
     * @return mixed
     */
    public function delete(User $user, HpeContractTemplate $hpeContractTemplate)
    {
        if ($hpeContractTemplate->isSystem()) {
            return $this->deny(QTSD_01);
        }

        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->cannot('delete_own_hpe_contract_templates')) {
            return false;
        }

        if ($hpeContractTemplate->user()->is($user)) {
            return true;
        }

        if ($this->userTeamGate->isLedByUser($hpeContractTemplate->user()->getParentKey(), $user)) {
            return true;
        }
    }

    /**
     * Determine whether the user can make copy of the template.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Template\HpeContractTemplate $hpeContractTemplate
     * @return mixed
     */
    public function copy(User $user, HpeContractTemplate $hpeContractTemplate)
    {
        return $this->create($user) && $this->view($user, $hpeContractTemplate);
    }
}
