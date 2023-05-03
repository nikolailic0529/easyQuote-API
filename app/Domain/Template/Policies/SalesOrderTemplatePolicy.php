<?php

namespace App\Domain\Template\Policies;

use App\Domain\Authentication\Services\UserTeamGate;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\SalesOrderTemplate;
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

        if (true === (bool) $salesOrderTemplate->is_system) {
            return $this->deny(QTSU_01);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
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

        if (true === (bool) $salesOrderTemplate->is_system) {
            return $this->deny(QTSD_01);
        }

        return true;
    }
}
