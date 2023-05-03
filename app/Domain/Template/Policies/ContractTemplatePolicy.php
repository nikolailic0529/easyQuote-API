<?php

namespace App\Domain\Template\Policies;

use App\Domain\Authentication\Services\UserTeamGate;
use App\Domain\Rescue\Models\ContractTemplate;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractTemplatePolicy
{
    use HandlesAuthorization;

    public function __construct(protected UserTeamGate $userTeamGate)
    {
    }

    /**
     * Determine whether the user can view any contract templates.
     *
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

        if ($user->cannot('update_own_contract_templates')) {
            return false;
        }

        if ($contractTemplate->user()->is($user)) {
            return true;
        }

        if ($this->userTeamGate->isLedByUser($contractTemplate->user()->getParentKey(), $user)) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the contract template.
     *
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

        if ($user->cannot('delete_own_contract_templates')) {
            return false;
        }

        if ($contractTemplate->user()->is($user)) {
            return true;
        }

        if ($this->userTeamGate->isLedByUser($contractTemplate->user()->getParentKey(), $user)) {
            return true;
        }
    }

    /**
     * Determine whether the user can make copy of the template.
     *
     * @return mixed
     */
    public function copy(User $user, ContractTemplate $contractTemplate)
    {
        return $this->create($user) && $this->view($user, $contractTemplate);
    }
}
