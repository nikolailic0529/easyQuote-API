<?php

namespace App\Policies;

use App\Models\Template\SalesOrderTemplate;
use App\Models\User;
use App\Services\Auth\UserTeamGate;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalesOrderTemplatePolicy
{
    use HandlesAuthorization;

    public function __construct(protected UserTeamGate $userTeamGate)
    {
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_sales_order_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template\SalesOrderTemplate  $salesOrderTemplate
     * @return mixed
     */
    public function view(User $user, SalesOrderTemplate $salesOrderTemplate)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('view_sales_order_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->hasRole(R_SUPER)) {
            return true;
        }

        if ($user->can('create_sales_order_templates')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template\SalesOrderTemplate  $salesOrderTemplate
     * @return mixed
     */
    public function update(User $user, SalesOrderTemplate $salesOrderTemplate)
    {
        $hasPermissionToUpdate = value(function () use ($salesOrderTemplate, $user): bool {
            if ($user->hasRole(R_SUPER)) {
                return true;
            }

            if ($user->cannot('update_own_sales_order_templates')) {
                return false;
            }

            if ($salesOrderTemplate->user()->is($user)) {
                return true;
            }

            if ($this->userTeamGate->isLedByUser($salesOrderTemplate->user()->getParentKey(), $user)) {
                return true;
            }

            return false;
        });

        if (false === $hasPermissionToUpdate) {
            return false;
        }

        if (true === (bool)$salesOrderTemplate->is_system) {
            return $this->deny(QTSU_01);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Template\SalesOrderTemplate  $salesOrderTemplate
     * @return mixed
     */
    public function delete(User $user, SalesOrderTemplate $salesOrderTemplate)
    {
        $hasPermissionToUpdate = value(function () use ($salesOrderTemplate, $user) {
            if ($user->hasRole(R_SUPER)) {
                return true;
            }

            if ($user->cannot('delete_own_sales_order_templates')) {
                return false;
            }

            if ($salesOrderTemplate->user()->is($user)) {
                return true;
            }

            if ($this->userTeamGate->isLedByUser($salesOrderTemplate->user()->getParentKey(), $user)) {
                return true;
            }

            return false;
        });

        if (false === $hasPermissionToUpdate) {
            return false;
        }

        if (true === (bool)$salesOrderTemplate->is_system) {
            return $this->deny(QTSD_01);
        }

        return true;
    }
}
